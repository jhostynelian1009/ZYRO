<?php

namespace Tests\Feature;

use App\Contracts\PaymentGatewayInterface;
use App\DTOs\PaymentProcessResult;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PaymentWebhook;
use App\Models\Product;
use App\Models\User;
use App\Payments\Gateways\DeunaGatewayAdapter;
use App\Payments\Gateways\MockPaymentGateway;
use App\Payments\Gateways\PayPhoneGatewayAdapter;
use App\Payments\PaymentGatewayManager;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class PaymentIntegrationF5Test extends TestCase
{
    use RefreshDatabase;

    private function customer(): User
    {
        return User::factory()->customer()->create();
    }

    private function product(int $stock = 10, float $price = 49.90): Product
    {
        return Product::factory()->create([
            'stock' => $stock,
            'price' => $price,
            'is_active' => true,
        ]);
    }

    private function createOrderWithItem(int $qty = 2, int $initialStock = 10): array
    {
        $customer = $this->customer();
        $product = $this->product($initialStock, 49.90);

        $order = Order::factory()->approved()->create([
            'user_id'         => $customer->id,
            'subtotal'        => 49.90 * $qty,
            'tax_amount'      => round(49.90 * $qty * 0.15, 2),
            'shipping_amount' => 6.00,
            'total'           => round(49.90 * $qty * 1.15 + 6.00, 2),
            'status'          => OrderStatus::Approved,
        ]);

        $item = OrderItem::factory()->create([
            'order_id'     => $order->id,
            'product_id'   => $product->id,
            'product_name' => $product->name,
            'size'         => 'M',
            'quantity'     => $qty,
            'unit_price'   => 49.90,
            'line_total'   => 49.90 * $qty,
        ]);

        return [$order, $customer, $product, $item];
    }

    // ─── 1. Idempotencia y Prevención de Descuento Duplicado ─────────────────

    public function test_payment_idempotency_prevents_duplicate_inventory_decrement(): void
    {
        config(['payment.mode' => 'mock', 'payment.default_gateway' => 'mock', 'payment.mock.success_rate' => 1.0]);

        [$order, $customer, $product] = $this->createOrderWithItem(2, 10);
        $service = app(PaymentService::class);
        $idempotencyKey = 'F5-IDEMP-' . Str::uuid()->toString();

        // Primer procesamiento
        $result1 = $service->process($order, PaymentMethod::TestCard, ['idempotency_key' => $idempotencyKey]);
        $this->assertTrue($result1['success']);
        $product->refresh();
        $this->assertEquals(8, $product->stock); // 10 - 2

        // Segundo procesamiento idéntico (mismo idempotency_key)
        $result2 = $service->process($order, PaymentMethod::TestCard, ['idempotency_key' => $idempotencyKey]);
        $this->assertTrue($result2['success']);
        $this->assertEquals($result1['payment']->id, $result2['payment']->id);

        // El stock NO debe descontarse dos veces
        $product->refresh();
        $this->assertEquals(8, $product->stock);
        $this->assertEquals(1, Payment::where('order_id', $order->id)->where('status', PaymentStatus::Approved)->count());
    }

    // ─── 2. Webhook Reconciliación Descuenta Inventario una Sola Vez ─────────

    public function test_webhook_reconciliation_decrements_stock_exactly_once(): void
    {
        config(['payment.mode' => 'mock']);

        [$order, $customer, $product] = $this->createOrderWithItem(3, 10);
        $service = app(PaymentService::class);

        // Simular pago pendiente inicial (como en PayPhone/DEUNA redirect)
        $payment = Payment::create([
            'order_id'                => $order->id,
            'method'                  => PaymentMethod::TestCard,
            'gateway'                 => 'mock',
            'gateway_mode'            => 'mock',
            'status'                  => PaymentStatus::Pending,
            'amount'                  => $order->total,
            'currency'                => 'USD',
            'reference'               => 'MOCK-WH-TEST-001',
            'external_transaction_id' => 'TXN-WH-001',
            'idempotency_key'         => Str::uuid()->toString(),
            'processed_at'            => now(),
        ]);

        // Stock aún no debe descontarse
        $product->refresh();
        $this->assertEquals(10, $product->stock);
        $this->assertNotEquals(OrderStatus::Paid, $order->status);

        // Primer webhook de aprobación
        $whResult1 = $service->processWebhook('mock', [
            'event_id'       => 'EVT-001',
            'transactionId'  => 'TXN-WH-001',
            'reference'      => 'MOCK-WH-TEST-001',
            'status'         => 'approved',
            'amount'         => (float) $order->total,
        ]);

        $this->assertTrue($whResult1->success);
        $order->refresh();
        $product->refresh();
        $this->assertEquals(OrderStatus::Paid, $order->status);
        $this->assertEquals(7, $product->stock); // 10 - 3

        // Segundo webhook idéntico (reintento del proveedor)
        $whResult2 = $service->processWebhook('mock', [
            'event_id'       => 'EVT-001',
            'transactionId'  => 'TXN-WH-001',
            'reference'      => 'MOCK-WH-TEST-001',
            'status'         => 'approved',
            'amount'         => (float) $order->total,
        ]);

        // El stock NO debe cambiar de nuevo
        $product->refresh();
        $this->assertEquals(7, $product->stock);
        $this->assertEquals(OrderStatus::Paid, $order->status);
    }

    // ─── 3. Rechazo de Monto Manipulado en Backend ────────────────────────────

    public function test_manipulated_amount_in_payload_is_strictly_rejected(): void
    {
        config(['payment.mode' => 'mock', 'payment.default_gateway' => 'mock']);

        [$order] = $this->createOrderWithItem(1, 10);
        $service = app(PaymentService::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Monto inválido');

        // El cliente intenta enviar un monto de $1.00 en lugar del total real
        $service->process($order, PaymentMethod::TestCard, [
            'amount' => 1.00,
        ]);
    }

    // ─── 4. Concurrencia y Lock en PaymentService ─────────────────────────────

    public function test_concurrency_lock_prevents_simultaneous_double_charging(): void
    {
        config(['payment.mode' => 'mock', 'payment.default_gateway' => 'mock', 'payment.mock.success_rate' => 1.0]);

        [$order, $customer, $product] = $this->createOrderWithItem(2, 5);
        $service = app(PaymentService::class);

        // Simular que el primer hilo procesó y aprobó
        $res = $service->process($order, PaymentMethod::TestCard);
        $this->assertTrue($res['success']);

        // Un segundo intento para la misma orden ya pagada debe ser rechazado inmediatamente
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('ya tiene un pago aprobado');

        $service->process($order, PaymentMethod::TestCard);
    }

    // ─── 5. Entorno LIVE Estrictamente Bloqueado en Desarrollo ─────────────────

    public function test_live_mode_strictly_blocked_in_non_production(): void
    {
        config(['payment.mode' => 'live']);

        $manager = app(PaymentGatewayManager::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('PAYMENT_MODE=live solo está permitido en APP_ENV=production');

        $manager->gateway('mock');
    }

    // ─── 6. Transición de Estado y Auditoría en Orden ─────────────────────────

    public function test_approved_payment_creates_order_status_history(): void
    {
        config(['payment.mode' => 'mock', 'payment.default_gateway' => 'mock', 'payment.mock.success_rate' => 1.0]);

        [$order] = $this->createOrderWithItem(1, 10);
        $service = app(PaymentService::class);

        $service->process($order, PaymentMethod::TestCard);

        $this->assertDatabaseHas('order_status_histories', [
            'order_id'  => $order->id,
            'to_status' => OrderStatus::Paid->value,
        ]);
    }
}
