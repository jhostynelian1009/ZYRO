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
use App\Payments\Clients\DeunaClient;
use App\Payments\Clients\PayPhoneClient;
use App\Payments\Gateways\DeunaGatewayAdapter;
use App\Payments\Gateways\PayPhoneGatewayAdapter;
use App\Payments\PaymentGatewayManager;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

/**
 * SandboxCertificationF5Test — Certificación formal de pasarelas Sandbox y Seguridad (F5.1).
 *
 * Cubre:
 * 1. Auditoría de contratos (PayPhone & DEUNA)
 * 2. Pruebas de seguridad (firmas inválidas, monedas incorrectas, eventos fuera de orden, importes manipulados)
 * 3. Integridad comercial (descuento único de stock, protección ante cancelaciones previas)
 */
class SandboxCertificationF5Test extends TestCase
{
    use RefreshDatabase;

    private string $payphoneToken = 'sandbox_token_pp_cert_2026';
    private string $payphoneStoreId = 'store-uuid-cert-9999';
    private string $deunaApiKey = 'sandbox_key_deuna_cert_2026';
    private string $deunaStoreCode = 'store_code_deuna_cert';
    private string $deunaWebhookSecret = 'webhook_secret_cert_deuna_2026';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'payment.mode'                 => 'sandbox',
            'payment.default_gateway'      => 'mock',
            'payment.currency'             => 'USD',
            'payment.payphone.token'       => $this->payphoneToken,
            'payment.payphone.store_id'    => $this->payphoneStoreId,
            'payment.payphone.sandbox_url' => 'https://pay.payphonetodoesposible.com/api',
            'payment.deuna.api_key'        => $this->deunaApiKey,
            'payment.deuna.store_code'     => $this->deunaStoreCode,
            'payment.deuna.webhook_secret' => $this->deunaWebhookSecret,
            'payment.deuna.sandbox_url'    => 'https://api-staging.deuna.com',
            'payment.webhook_secret'       => 'global_wh_secret_cert_2026',
        ]);
    }

    private function createOrder(float $amount = 100.00, int $stock = 10): array
    {
        $customer = User::factory()->customer()->create([
            'name'  => 'Carlos Andrade',
            'email' => 'carlos.andrade@zyro.test',
        ]);

        $product = Product::factory()->create([
            'name'  => 'Camiseta Zyro Lunar',
            'price' => $amount,
            'stock' => $stock,
            'is_active' => true,
        ]);

        $order = Order::factory()->approved()->create([
            'order_number'    => 'ORD-CERT-' . rand(1000, 9999),
            'user_id'         => $customer->id,
            'customer_name'   => 'Carlos Andrade',
            'phone'           => '0991234567',
            'address'         => 'Av. Amazonas y Colón',
            'city'            => 'Quito',
            'subtotal'        => $amount,
            'tax_amount'      => round($amount * 0.15, 2),
            'shipping_amount' => 6.00,
            'total'           => round($amount * 1.15 + 6.00, 2),
            'status'          => OrderStatus::Approved,
        ]);

        $item = OrderItem::factory()->create([
            'order_id'     => $order->id,
            'product_id'   => $product->id,
            'product_name' => $product->name,
            'quantity'     => 1,
            'unit_price'   => $amount,
            'line_total'   => $amount,
        ]);

        return [$order, $product, $customer, $item];
    }

    // ─── 1. Auditoría de Contratos: PayPhone ──────────────────────────────────

    public function test_payphone_contract_verification_prepare_and_confirm(): void
    {
        [$order, $product] = $this->createOrder(100.00);
        $clientTxId = (string) Str::uuid();

        Http::fake([
            'https://pay.payphonetodoesposible.com/api/button/Prepare' => Http::response([
                'paymentId'   => 998877,
                'payWithCard' => 'https://pay.payphonetodoesposible.com/api/button/pay?id=998877',
            ], 200),
            'https://pay.payphonetodoesposible.com/api/button/Confirm' => Http::response([
                'transactionId'       => 998877,
                'clientTransactionId' => $clientTxId,
                'statusCode'          => 3,
                'transactionStatus'   => 'Approved',
                'amount'              => 12100, // 100 * 1.15 + 6.00 = 121.00 => 12100 cents
            ], 200),
        ]);

        $service = app(PaymentService::class);
        $initResult = $service->process($order, PaymentMethod::TestCard, ['idempotency_key' => $clientTxId], 'payphone');

        $this->assertFalse($initResult['success']);
        $this->assertEquals('Redirigiendo a la pasarela de pago.', $initResult['message']);

        // Verificar contrato en POST /button/Prepare
        Http::assertSent(function (Request $request) use ($clientTxId) {
            if ($request->url() !== 'https://pay.payphonetodoesposible.com/api/button/Prepare') {
                return false;
            }
            $data = $request->data();
            return $request->hasHeader('Authorization', 'Bearer ' . $this->payphoneToken)
                && $data['storeId'] === $this->payphoneStoreId
                && $data['clientTransactionId'] === $clientTxId
                && $data['amount'] === 12100
                && $data['amountWithTax'] === 10000
                && $data['tax'] === 1500;
        });

        // Simular webhook y validar contrato en POST /button/Confirm
        $this->postJson('/webhooks/payment/payphone', [
            'id'         => 998877,
            'clientTxId' => $clientTxId,
        ])->assertStatus(200)->assertJson(['success' => true, 'status' => 'approved']);

        Http::assertSent(function (Request $request) use ($clientTxId) {
            if ($request->url() !== 'https://pay.payphonetodoesposible.com/api/button/Confirm') {
                return false;
            }
            $data = $request->data();
            return $request->hasHeader('Authorization', 'Bearer ' . $this->payphoneToken)
                && $data['id'] === 998877
                && $data['clientTxId'] === $clientTxId;
        });

        $product->refresh();
        $this->assertEquals(9, $product->stock); // Descontado exactamente 1
    }

    // ─── 2. Auditoría de Contratos: DEUNA ─────────────────────────────────────

    public function test_deuna_contract_verification_headers_and_payload(): void
    {
        [$order, $product] = $this->createOrder(50.00);
        $orderToken = 'tok_deuna_contract_test_50';

        Http::fake([
            'https://api-staging.deuna.com/merchants/orders' => Http::response([
                'order_token'  => $orderToken,
                'checkout_url' => 'https://api-staging.deuna.com/checkout?order_token=' . $orderToken,
                'status'       => 'pending',
            ], 200),
        ]);

        $service = app(PaymentService::class);
        $initResult = $service->process($order, PaymentMethod::TestCard, [], 'deuna');

        $this->assertFalse($initResult['success']);

        // Verificar contrato en POST /merchants/orders
        Http::assertSent(function (Request $request) use ($order) {
            if ($request->url() !== 'https://api-staging.deuna.com/merchants/orders') {
                return false;
            }
            $hasKey = $request->hasHeader('X-Api-Key', $this->deunaApiKey);
            $hasStore = $request->hasHeader('X-Store-Code', $this->deunaStoreCode);
            $data = $request->data();
            $hasOrderId = $data['order']['order_id'] === $order->order_number;
            $hasTotal = $data['order']['total_amount'] === (int) round($order->total * 100);

            return $hasKey && $hasStore && $hasOrderId && $hasTotal;
        });
    }

    // ─── 3. Seguridad: Firma Criptográfica Inválida DEUNA ─────────────────────

    public function test_security_deuna_tampered_signature_is_strictly_rejected(): void
    {
        [$order] = $this->createOrder(60.00);
        $payload = [
            'event_id'       => 'evt_tampered_001',
            'order_token'    => 'tok_fake_001',
            'status'         => 'paid',
            'amount'         => 60.00,
            'transaction_id' => 'tx_tampered_001',
        ];

        // Se envía una firma manipulada que no coincide con el HMAC-SHA256 Base64
        $response = $this->withHeaders([
            'X-Signature' => 'InvalidTamperedSignatureBase64==',
        ])->postJson('/webhooks/payment/deuna', $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => false,
            'status'  => 'failed',
        ]);

        $this->assertStringContainsString('firma inválida', strtolower($response->json('message')));
    }

    // ─── 4. Seguridad: Moneda Incorrecta Rechazada ────────────────────────────

    public function test_security_non_usd_currency_in_process_is_rejected(): void
    {
        [$order] = $this->createOrder(100.00);
        $service = app(PaymentService::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Moneda no soportada: [EUR]');

        $service->process($order, PaymentMethod::TestCard, [
            'currency' => 'EUR',
        ], 'mock');
    }

    public function test_security_non_usd_currency_in_webhook_is_rejected(): void
    {
        $service = app(PaymentService::class);

        $result = $service->processWebhook('mock', [
            'event_id'  => 'EVT-EUR-001',
            'reference' => 'REF-EUR-001',
            'status'    => 'approved',
            'currency'  => 'EUR',
            'amount'    => 100.00,
        ]);

        $this->assertFalse($result->success);
        $this->assertEquals(PaymentStatus::Failed, $result->status);
        $this->assertStringContainsString('Moneda inválida', $result->message);
    }

    // ─── 5. Seguridad: Evento Fuera de Orden (Orden Cancelada Previamente) ─────

    public function test_security_late_webhook_does_not_resurrect_cancelled_order(): void
    {
        [$order, $product] = $this->createOrder(80.00, 10);
        $clientTxId = (string) Str::uuid();

        // 1. Crear pago pendiente
        $payment = Payment::create([
            'order_id'                => $order->id,
            'method'                  => PaymentMethod::TestCard,
            'gateway'                 => 'payphone',
            'gateway_mode'            => 'sandbox',
            'status'                  => PaymentStatus::Pending,
            'amount'                  => $order->total,
            'currency'                => 'USD',
            'reference'               => $clientTxId,
            'external_transaction_id' => '999888',
            'idempotency_key'         => $clientTxId,
            'processed_at'            => now(),
        ]);

        // 2. La orden es cancelada (por ejemplo, por tiempo expirado o el usuario)
        $order->update(['status' => OrderStatus::Cancelled]);

        // 3. Llega tardíamente un webhook confirmando la transacción en PayPhone
        Http::fake([
            'https://pay.payphonetodoesposible.com/api/button/Confirm' => Http::response([
                'transactionId'       => 999888,
                'clientTransactionId' => $clientTxId,
                'statusCode'          => 3,
                'transactionStatus'   => 'Approved',
                'amount'              => 8000,
            ], 200),
        ]);

        $response = $this->postJson('/webhooks/payment/payphone', [
            'id'         => 999888,
            'clientTxId' => $clientTxId,
        ]);

        $response->assertStatus(200);

        // La orden debe PERMANECER Cancelled y el stock intacto (10)
        $order->refresh();
        $this->assertEquals(OrderStatus::Cancelled, $order->status);

        $product->refresh();
        $this->assertEquals(10, $product->stock); // No se descontó inventario

        $payment->refresh();
        $this->assertEquals(PaymentStatus::Failed, $payment->status);
        $this->assertStringContainsString('cancelled', strtolower($payment->error_message));
    }

    // ─── 6. Integridad Comercial: Reintentos y No Duplicación ──────────────────

    public function test_commercial_integrity_duplicate_retries_preserve_single_decrement(): void
    {
        [$order, $product] = $this->createOrder(70.00, 10);
        $orderToken = 'tok_deuna_retry_test';

        // 1. Simular pago pendiente DEUNA
        $payment = Payment::create([
            'order_id'                => $order->id,
            'method'                  => PaymentMethod::TestCard,
            'gateway'                 => 'deuna',
            'gateway_mode'            => 'sandbox',
            'status'                  => PaymentStatus::Pending,
            'amount'                  => $order->total,
            'currency'                => 'USD',
            'reference'               => $orderToken,
            'external_transaction_id' => 'tx_deuna_retry_1',
            'idempotency_key'         => Str::uuid()->toString(),
            'processed_at'            => now(),
        ]);

        $webhookPayload = [
            'event_id'       => 'evt_retry_100',
            'order_token'    => $orderToken,
            'status'         => 'paid',
            'amount'         => (float) $order->total,
            'transaction_id' => 'tx_deuna_retry_1',
        ];
        $rawPayload = json_encode($webhookPayload);
        $client = app(DeunaClient::class);
        $signature = $client->computeWebhookSignature($rawPayload, $this->deunaWebhookSecret);

        // Primer envío del webhook
        $this->withHeaders(['X-Signature' => $signature])
            ->postJson('/webhooks/payment/deuna', $webhookPayload)
            ->assertStatus(200);

        $product->refresh();
        $order->refresh();
        $this->assertEquals(9, $product->stock); // 10 - 1
        $this->assertEquals(OrderStatus::Paid, $order->status);

        // Segundo envío (reintento del proveedor con el mismo event_id)
        $this->withHeaders(['X-Signature' => $signature])
            ->postJson('/webhooks/payment/deuna', $webhookPayload)
            ->assertStatus(200);

        // Tercer envío (reintento con diferente event_id pero misma orden ya pagada)
        $webhookPayload2 = array_merge($webhookPayload, ['event_id' => 'evt_retry_101']);
        $rawPayload2 = json_encode($webhookPayload2);
        $sig2 = $client->computeWebhookSignature($rawPayload2, $this->deunaWebhookSecret);

        $this->withHeaders(['X-Signature' => $sig2])
            ->postJson('/webhooks/payment/deuna', $webhookPayload2)
            ->assertStatus(200);

        // El inventario permanece en 9 y nunca se descuenta de nuevo
        $product->refresh();
        $this->assertEquals(9, $product->stock);
        $this->assertEquals(OrderStatus::Paid, $order->status);
    }

    // ─── 7. Seguridad: Identificadores Inexistentes ────────────────────────────

    public function test_security_webhook_for_nonexistent_transaction_handled_safely(): void
    {
        $response = $this->postJson('/webhooks/payment/mock', [
            'event_id'  => 'EVT-NONEXISTENT-999',
            'reference' => 'NONEXISTENT-REF-999',
            'status'    => 'approved',
            'amount'    => 50.00,
        ]);

        $response->assertStatus(200);
        $this->assertEquals('approved', $response->json('status'));
        // Verifica que no rompió la BD ni creó pagos huérfanos
        $this->assertEquals(0, Payment::where('reference', 'NONEXISTENT-REF-999')->count());
    }
}
