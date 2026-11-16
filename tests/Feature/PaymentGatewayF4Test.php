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
use App\Payments\Gateways\BankTransferGateway;
use App\Payments\Gateways\CashPaymentGateway;
use App\Payments\Gateways\DeunaGatewayAdapter;
use App\Payments\Gateways\MockPaymentGateway;
use App\Payments\Gateways\PayPhoneGatewayAdapter;
use App\Payments\PaymentGatewayManager;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class PaymentGatewayF4Test extends TestCase
{
    use RefreshDatabase;

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function customer(): User
    {
        return User::factory()->customer()->create();
    }

    private function product(array $attrs = []): Product
    {
        return Product::factory()->create(array_merge(['stock' => 10, 'price' => 49.90], $attrs));
    }

    private function approvedOrderWithItem(int $qty = 2): array
    {
        $customer = $this->customer();
        $product  = $this->product(['stock' => 10]);

        $order = Order::factory()->approved()->create([
            'user_id'         => $customer->id,
            'subtotal'        => 49.90 * $qty,
            'tax_amount'      => round(49.90 * $qty * 0.15, 2),
            'shipping_amount' => 6.00,
            'total'           => round(49.90 * $qty * 1.15 + 6.00, 2),
        ]);

        OrderItem::factory()->create([
            'order_id'     => $order->id,
            'product_id'   => $product->id,
            'product_name' => $product->name,
            'size'         => 'M',
            'quantity'     => $qty,
            'unit_price'   => 49.90,
            'line_total'   => 49.90 * $qty,
        ]);

        return [$order, $customer, $product];
    }

    // ─── 1. Mock Gateway: pago aprobado ──────────────────────────────────────

    public function test_mock_gateway_approves_payment(): void
    {
        config(['payment.mode' => 'mock', 'payment.default_gateway' => 'mock', 'payment.mock.success_rate' => 1.0]);

        [$order, $customer, $product] = $this->approvedOrderWithItem(2);

        $service = app(PaymentService::class);
        $result  = $service->process($order, PaymentMethod::TestCard);

        $this->assertTrue($result['success']);
        $this->assertNotNull($result['payment']);
        $this->assertEquals(PaymentStatus::Approved, $result['payment']->status);
        $this->assertEquals('mock', $result['payment']->gateway);
        $this->assertNotNull($result['payment']->idempotency_key);
        $this->assertEquals('USD', $result['payment']->currency);

        // Stock debe haber decrementado.
        $product->refresh();
        $this->assertEquals(8, $product->stock); // 10 - 2

        // Pedido debe estar pagado.
        $order->refresh();
        $this->assertEquals(OrderStatus::Paid, $order->status);
    }

    // ─── 2. Mock Gateway: pago rechazado ─────────────────────────────────────

    public function test_mock_gateway_rejects_payment_with_zero_success_rate(): void
    {
        config(['payment.mode' => 'mock', 'payment.default_gateway' => 'mock', 'payment.mock.success_rate' => 0.0]);

        [$order, $customer, $product] = $this->approvedOrderWithItem(2);

        $service = app(PaymentService::class);
        $result  = $service->process($order, PaymentMethod::TestCard);

        $this->assertFalse($result['success']);
        $this->assertEquals(PaymentStatus::Failed, $result['payment']->status);

        // Stock no debe haber cambiado.
        $product->refresh();
        $this->assertEquals(10, $product->stock);

        // Pedido sigue aprobado (no cambia a pagado).
        $order->refresh();
        $this->assertNotEquals(OrderStatus::Paid, $order->status);
    }

    // ─── 3. Estado cancelado ─────────────────────────────────────────────────

    public function test_cancelled_order_cannot_be_paid(): void
    {
        $customer = $this->customer();
        $product  = $this->product();

        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'status'  => OrderStatus::Cancelled,
            'total'   => 99.80,
        ]);

        config(['payment.mode' => 'mock', 'payment.default_gateway' => 'mock']);

        $service = app(PaymentService::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('no puede procesarse');

        $service->process($order, PaymentMethod::TestCard);
    }

    // ─── 4. Error de conexión (excepción del gateway) ────────────────────────

    public function test_gateway_exception_propagates_correctly(): void
    {
        config(['payment.mode' => 'mock', 'payment.default_gateway' => 'mock']);

        [$order] = $this->approvedOrderWithItem();

        // Crear un mock de la interfaz que lance excepción.
        $fakeGateway = $this->createMock(PaymentGatewayInterface::class);
        $fakeGateway->method('getIdentifier')->willReturn('mock');
        $fakeGateway->method('getMode')->willReturn('mock');
        $fakeGateway->method('charge')->willThrowException(
            new RuntimeException('Simulated connection error')
        );

        // Reemplazar el manager para que devuelva nuestro mock.
        $manager = $this->createMock(PaymentGatewayManager::class);
        $manager->method('gateway')->willReturn($fakeGateway);

        $service = new PaymentService($manager);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Simulated connection error');

        $service->process($order, PaymentMethod::TestCard);
    }

    // ─── 5. Webhook duplicado (idempotencia) ─────────────────────────────────

    public function test_duplicate_webhook_is_idempotent(): void
    {
        config(['payment.mode' => 'mock', 'payment.default_gateway' => 'mock']);

        $service = app(PaymentService::class);

        $payload = [
            'event_id'  => 'EVT-UNIQUE-001',
            'reference' => 'MOCK-REF-001',
            'status'    => 'approved',
            'amount'    => 99.80,
        ];

        // Primer webhook.
        $result1 = $service->processWebhook('mock', $payload);
        $this->assertTrue($result1->success);

        // Segundo webhook con mismo event_id.
        $result2 = $service->processWebhook('mock', $payload);
        $this->assertTrue($result2->success);

        // Solo debe existir un registro en payment_webhooks.
        $this->assertEquals(1, PaymentWebhook::query()->where('external_event_id', 'EVT-UNIQUE-001')->count());
    }

    // ─── 6. Monto manipulado rechazado ───────────────────────────────────────

    public function test_manipulated_amount_is_rejected(): void
    {
        config(['payment.mode' => 'mock', 'payment.default_gateway' => 'mock']);

        [$order] = $this->approvedOrderWithItem(2);

        $service = app(PaymentService::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Monto inválido');

        // Intentar pagar con un monto diferente al del pedido.
        $service->process($order, PaymentMethod::TestCard, ['amount' => 1.00]);
    }

    // ─── 7. Pedido inexistente retorna error ─────────────────────────────────

    public function test_nonexistent_order_fails(): void
    {
        config(['payment.mode' => 'mock', 'payment.default_gateway' => 'mock']);

        $fakeOrder = new Order();
        $fakeOrder->id = 999999;
        $fakeOrder->status = OrderStatus::Approved;
        $fakeOrder->total = 50.00;

        $service = app(PaymentService::class);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $service->process($fakeOrder, PaymentMethod::TestCard);
    }

    // ─── 8. Transacción duplicada (misma idempotency_key) ────────────────────

    public function test_duplicate_idempotency_key_returns_existing_payment(): void
    {
        config(['payment.mode' => 'mock', 'payment.default_gateway' => 'mock', 'payment.mock.success_rate' => 1.0]);

        [$order] = $this->approvedOrderWithItem(2);

        $idempotencyKey = Str::uuid()->toString();
        $service = app(PaymentService::class);

        // Primer pago.
        $result1 = $service->process($order, PaymentMethod::TestCard, ['idempotency_key' => $idempotencyKey]);
        $this->assertTrue($result1['success']);

        // Segundo pago con misma idempotency_key: debe retornar el existente sin crear otro.
        $result2 = $service->process($order->fresh(), PaymentMethod::TestCard, ['idempotency_key' => $idempotencyKey]);
        $this->assertTrue($result2['success']);
        $this->assertEquals($result1['payment']->id, $result2['payment']->id);
        $this->assertStringContainsString('idempotencia', $result2['message']);
    }

    // ─── 9. Reconciliación: pending → approved vía webhook ───────────────────

    public function test_webhook_reconciles_pending_payment_to_approved(): void
    {
        config(['payment.mode' => 'mock', 'payment.default_gateway' => 'mock']);

        [$order, $customer, $product] = $this->approvedOrderWithItem(1);

        // Crear un pago pendiente manualmente (como si PayPhone hubiera redirigido).
        $payment = Payment::query()->create([
            'order_id'                => $order->id,
            'method'                  => PaymentMethod::TestCard,
            'gateway'                 => 'mock',
            'gateway_mode'            => 'mock',
            'status'                  => PaymentStatus::Pending,
            'amount'                  => $order->total,
            'currency'                => 'USD',
            'reference'               => 'PP-PENDING-001',
            'external_transaction_id' => 'PP-PENDING-001',
            'idempotency_key'         => Str::uuid()->toString(),
            'processed_at'            => now(),
        ]);

        $service = app(PaymentService::class);

        // Simular webhook que confirma la aprobación.
        $webhookPayload = [
            'event_id'       => 'EVT-RECONCILE-001',
            'reference'      => 'PP-PENDING-001',
            'transaction_id' => 'PP-PENDING-001',
            'status'         => 'approved',
            'amount'         => $order->total,
        ];

        $result = $service->processWebhook('mock', $webhookPayload);
        $this->assertTrue($result->success);

        // El pago debe haberse actualizado a aprobado.
        $payment->refresh();
        $this->assertEquals(PaymentStatus::Approved, $payment->status);

        // El pedido debe haberse marcado como pagado.
        $order->refresh();
        $this->assertEquals(OrderStatus::Paid, $order->status);
    }

    // ─── 10. Pago en efectivo (POS/presencial) ───────────────────────────────

    public function test_cash_payment_gateway_approves_immediately(): void
    {
        config(['payment.mode' => 'mock', 'payment.default_gateway' => 'cash']);

        [$order, $customer, $product] = $this->approvedOrderWithItem(1);

        $service = app(PaymentService::class);
        $result  = $service->process($order, PaymentMethod::Cash, [
            'operator_id' => $customer->id,
            'channel'     => 'presencial',
        ], 'cash');

        $this->assertTrue($result['success']);
        $this->assertEquals(PaymentStatus::Approved, $result['payment']->status);
        $this->assertEquals('cash', $result['payment']->gateway);
        $this->assertStringStartsWith('CASH-', $result['payment']->reference);

        $order->refresh();
        $this->assertEquals(OrderStatus::Paid, $order->status);

        $product->refresh();
        $this->assertEquals(9, $product->stock); // 10 - 1
    }

    // ─── 11. MOCK en producción lanza excepción ──────────────────────────────

    public function test_mock_gateway_throws_in_production(): void
    {
        // Simular entorno de producción.
        app()->detectEnvironment(fn () => 'production');

        config(['payment.mode' => 'mock', 'payment.default_gateway' => 'mock']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('no está permitido');

        $manager = new PaymentGatewayManager();
        $manager->gateway('mock');
    }

    // ─── Tests adicionales de adaptadores ────────────────────────────────────

    public function test_gateway_manager_resolves_all_adapters(): void
    {
        config(['payment.mode' => 'mock', 'payment.default_gateway' => 'mock']);

        $manager = new PaymentGatewayManager();

        $this->assertInstanceOf(MockPaymentGateway::class, $manager->gateway('mock'));
        $this->assertInstanceOf(CashPaymentGateway::class, $manager->gateway('cash'));
        $this->assertInstanceOf(BankTransferGateway::class, $manager->gateway('bank_transfer'));
        $this->assertInstanceOf(PayPhoneGatewayAdapter::class, $manager->gateway('payphone'));
        $this->assertInstanceOf(DeunaGatewayAdapter::class, $manager->gateway('deuna'));
    }

    public function test_unknown_gateway_throws(): void
    {
        config(['payment.mode' => 'mock']);

        $manager = new PaymentGatewayManager();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Pasarela de pago desconocida');

        $manager->gateway('bitcoin');
    }

    public function test_bank_transfer_stays_pending(): void
    {
        config(['payment.mode' => 'mock', 'payment.default_gateway' => 'bank_transfer']);

        [$order] = $this->approvedOrderWithItem(1);

        $service = app(PaymentService::class);
        $result  = $service->process($order, PaymentMethod::Transfer, [], 'bank_transfer');

        // El pago por transferencia queda pendiente.
        $this->assertFalse($result['success']);
        $this->assertEquals(PaymentStatus::Pending, $result['payment']->status);
        $this->assertEquals('bank_transfer', $result['payment']->gateway);
        $this->assertStringStartsWith('TRF-', $result['payment']->reference);

        // Pedido NO debe estar pagado.
        $order->refresh();
        $this->assertNotEquals(OrderStatus::Paid, $order->status);
    }

    public function test_payphone_returns_redirect(): void
    {
        config(['payment.mode' => 'sandbox', 'payment.default_gateway' => 'payphone']);

        [$order] = $this->approvedOrderWithItem(1);

        $gateway = new PayPhoneGatewayAdapter();
        $result  = $gateway->charge($order, []);

        $this->assertTrue($result->requiresRedirect);
        $this->assertNotNull($result->redirectUrl);
        $this->assertStringContainsString('payphone', $result->redirectUrl);
        $this->assertEquals(PaymentStatus::Pending, $result->status);
    }

    public function test_deuna_returns_redirect(): void
    {
        config(['payment.mode' => 'sandbox', 'payment.default_gateway' => 'deuna']);

        [$order] = $this->approvedOrderWithItem(1);

        $gateway = new DeunaGatewayAdapter();
        $result  = $gateway->charge($order, []);

        $this->assertTrue($result->requiresRedirect);
        $this->assertNotNull($result->redirectUrl);
        $this->assertStringContainsString('deuna', $result->redirectUrl);
        $this->assertEquals(PaymentStatus::Pending, $result->status);
    }

    public function test_webhook_route_rejects_unknown_gateway(): void
    {
        $response = $this->postJson('/webhooks/payment/bitcoin', ['test' => true]);
        $response->assertStatus(404);
    }

    public function test_webhook_route_accepts_valid_gateway(): void
    {
        config(['payment.mode' => 'mock', 'payment.default_gateway' => 'mock']);

        $response = $this->postJson('/webhooks/payment/mock', [
            'event_id'  => 'TEST-WH-001',
            'reference' => 'MOCK-TEST-001',
            'status'    => 'approved',
            'amount'    => 50.00,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['success', 'status', 'reference', 'message']);
    }

    public function test_live_mode_blocked_outside_production(): void
    {
        config(['payment.mode' => 'live']);

        $manager = new PaymentGatewayManager();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('PAYMENT_MODE=live solo está permitido en APP_ENV=production');

        $manager->gateway('payphone');
    }

    public function test_config_payment_file_loaded(): void
    {
        $this->assertNotNull(config('payment.mode'));
        $this->assertNotNull(config('payment.default_gateway'));
        $this->assertEquals('USD', config('payment.currency'));
    }

    public function test_payment_process_result_dto_factories(): void
    {
        $approved = PaymentProcessResult::approved('REF-001', 50.0);
        $this->assertTrue($approved->success);
        $this->assertEquals(PaymentStatus::Approved, $approved->status);
        $this->assertFalse($approved->requiresRedirect);

        $failed = PaymentProcessResult::failed('REF-002', 50.0, 'Rechazado');
        $this->assertFalse($failed->success);
        $this->assertEquals(PaymentStatus::Failed, $failed->status);

        $redirect = PaymentProcessResult::redirect('REF-003', 50.0, 'https://example.com/pay');
        $this->assertFalse($redirect->success);
        $this->assertTrue($redirect->requiresRedirect);
        $this->assertEquals('https://example.com/pay', $redirect->redirectUrl);
    }
}
