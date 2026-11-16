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
use App\Models\Product;
use App\Models\User;
use App\Payments\Clients\DeunaClient;
use App\Payments\Gateways\DeunaGatewayAdapter;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class DeunaSandboxTest extends TestCase
{
    use RefreshDatabase;

    private string $apiKey = 'test_deuna_api_key_sandbox';
    private string $storeCode = 'zyro_store_sandbox_01';
    private string $webhookSecret = 'secret_deuna_webhook_2026';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'payment.mode'                 => 'sandbox',
            'payment.default_gateway'      => 'deuna',
            'payment.currency'             => 'USD',
            'payment.deuna.api_key'        => $this->apiKey,
            'payment.deuna.store_code'     => $this->storeCode,
            'payment.deuna.webhook_secret' => $this->webhookSecret,
            'payment.deuna.sandbox_url'    => 'https://api.sandbox.deuna.io',
            'payment.deuna.live_url'       => 'https://api.deuna.com',
        ]);
    }

    private function createTestOrder(float $amount = 100.00): Order
    {
        $customer = User::factory()->customer()->create([
            'name'  => 'María López',
            'email' => 'maria@zyro.test',
        ]);

        $product = Product::factory()->create([
            'name'  => 'Chaqueta Zyro Tech',
            'price' => $amount,
            'stock' => 5,
        ]);

        $order = Order::factory()->approved()->create([
            'order_number'    => 'ORD-DEUNA-' . rand(1000, 9999),
            'user_id'         => $customer->id,
            'customer_name'   => 'María López',
            'phone'           => '0987654321',
            'address'         => 'Av. República de El Salvador',
            'city'            => 'Quito',
            'subtotal'        => $amount,
            'tax_amount'      => round($amount * 0.15, 2),
            'shipping_amount' => 5.00,
            'total'           => round($amount * 1.15 + 5.00, 2),
        ]);

        OrderItem::factory()->create([
            'order_id'     => $order->id,
            'product_id'   => $product->id,
            'product_name' => $product->name,
            'quantity'     => 1,
            'unit_price'   => $amount,
            'line_total'   => $amount,
        ]);

        return $order;
    }

    public function test_charge_creates_order_and_returns_redirect_with_checkout_url(): void
    {
        $order = $this->createTestOrder(80.00);
        $expectedOrderToken = 'tok_deuna_order_888';
        $expectedCheckoutUrl = 'https://api.sandbox.deuna.io/checkout?order_token=' . $expectedOrderToken;

        Http::fake([
            'https://api.sandbox.deuna.io/merchants/orders' => Http::response([
                'order_token'  => $expectedOrderToken,
                'checkout_url' => $expectedCheckoutUrl,
                'status'       => 'pending',
            ], 200),
        ]);

        $gateway = app(DeunaGatewayAdapter::class);
        $idempotencyKey = (string) Str::uuid();

        $result = $gateway->charge($order, ['idempotency_key' => $idempotencyKey]);

        $this->assertTrue($result->requiresRedirect);
        $this->assertEquals(PaymentStatus::Pending, $result->status);
        $this->assertEquals($expectedOrderToken, $result->reference);
        $this->assertEquals($expectedCheckoutUrl, $result->redirectUrl);
        $this->assertEquals($expectedOrderToken, $result->externalTransactionId);
        $this->assertEquals((float) $order->total, $result->amount);
        $this->assertEquals($idempotencyKey, $result->idempotencyKey);

        Http::assertSent(function (Request $request) use ($order, $idempotencyKey) {
            $hasCorrectUrl = $request->url() === 'https://api.sandbox.deuna.io/merchants/orders';
            $hasApiKey = $request->hasHeader('X-API-KEY', $this->apiKey);
            $hasIdempotency = $request->hasHeader('X-Idempotency-Key', $idempotencyKey);
            $hasStoreCode = $request->hasHeader('X-Store-Code', $this->storeCode);
            $hasContentType = $request->header('Content-Type')[0] === 'application/json';

            $data = $request->data();
            $hasOrderId = isset($data['order']['order_id']) && $data['order']['order_id'] === $order->order_number;

            return $hasCorrectUrl && $hasApiKey && $hasIdempotency && $hasStoreCode && $hasContentType && $hasOrderId;
        });
    }

    public function test_query_transaction_status_approved(): void
    {
        $token = 'tok_query_approved_123';
        $txId = 'tx_deuna_trans_999';

        Http::fake([
            "https://api.sandbox.deuna.io/merchants/orders/{$token}" => Http::response([
                'order_token'    => $token,
                'status'         => 'paid',
                'amount'         => 97.00,
                'transaction_id' => $txId,
            ], 200),
        ]);

        $gateway = app(DeunaGatewayAdapter::class);
        $result = $gateway->queryTransaction($token);

        $this->assertTrue($result->success);
        $this->assertEquals(PaymentStatus::Approved, $result->status);
        $this->assertEquals($token, $result->reference);
        $this->assertEquals(97.00, $result->amount);
        $this->assertEquals($txId, $result->externalTransactionId);
        $this->assertStringContainsString('aprobada', strtolower($result->message));

        Http::assertSent(function (Request $request) use ($token) {
            return $request->url() === "https://api.sandbox.deuna.io/merchants/orders/{$token}"
                && $request->hasHeader('X-API-KEY', $this->apiKey)
                && $request->hasHeader('X-Store-Code', $this->storeCode);
        });
    }

    public function test_query_transaction_status_pending_and_failed(): void
    {
        $tokenPending = 'tok_pending_111';
        $tokenFailed = 'tok_failed_222';

        Http::fake([
            "https://api.sandbox.deuna.io/merchants/orders/{$tokenPending}" => Http::response([
                'order_token' => $tokenPending,
                'status'      => 'pending',
                'amount'      => 45.00,
            ], 200),
            "https://api.sandbox.deuna.io/merchants/orders/{$tokenFailed}" => Http::response([
                'order_token' => $tokenFailed,
                'status'      => 'rejected',
                'amount'      => 45.00,
            ], 200),
        ]);

        $gateway = app(DeunaGatewayAdapter::class);

        // Pending
        $resultPending = $gateway->queryTransaction($tokenPending);
        $this->assertFalse($resultPending->success);
        $this->assertEquals(PaymentStatus::Pending, $resultPending->status);

        // Failed
        $resultFailed = $gateway->queryTransaction($tokenFailed);
        $this->assertFalse($resultFailed->success);
        $this->assertEquals(PaymentStatus::Failed, $resultFailed->status);
        $this->assertStringContainsString('rechazada', strtolower($resultFailed->message));
    }

    public function test_webhook_with_valid_signature_approves_payment(): void
    {
        $payload = [
            'order_token'    => 'tok_webhook_ok_555',
            'status'         => 'approved',
            'amount'         => 120.00,
            'transaction_id' => 'tx_deuna_555',
        ];
        $rawPayload = json_encode($payload);

        $client = app(DeunaClient::class);
        $signature = $client->computeWebhookSignature($rawPayload, $this->webhookSecret);

        $gateway = app(DeunaGatewayAdapter::class);
        $result = $gateway->handleWebhook($payload, ['X-Signature' => $signature]);

        $this->assertTrue($result->success);
        $this->assertEquals(PaymentStatus::Approved, $result->status);
        $this->assertEquals('tok_webhook_ok_555', $result->reference);
        $this->assertEquals(120.00, $result->amount);
        $this->assertEquals('tx_deuna_555', $result->externalTransactionId);
    }

    public function test_webhook_with_invalid_signature_is_rejected(): void
    {
        $payload = [
            'order_token'    => 'tok_webhook_fraud_000',
            'status'         => 'approved',
            'amount'         => 500.00,
            'transaction_id' => 'tx_fraud',
        ];

        $gateway = app(DeunaGatewayAdapter::class);
        $result = $gateway->handleWebhook($payload, ['X-Signature' => 'fake_signature_base64==']);

        $this->assertFalse($result->success);
        $this->assertEquals(PaymentStatus::Failed, $result->status);
        $this->assertStringContainsString('firma inválida', $result->message);
    }

    public function test_webhook_endpoint_http_route_with_signature(): void
    {
        $payload = [
            'event_id'       => 'evt_http_valid_101',
            'order_token'    => 'tok_http_webhook_777',
            'status'         => 'approved',
            'amount'         => 65.00,
            'transaction_id' => 'tx_http_777',
        ];
        $rawPayload = json_encode($payload);

        $client = app(DeunaClient::class);
        $signature = $client->computeWebhookSignature($rawPayload, $this->webhookSecret);

        // Envío válido
        $response = $this->withHeaders([
            'X-Signature' => $signature,
        ])->postJson('/webhooks/payment/deuna', $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success'   => true,
            'status'    => 'approved',
            'reference' => 'tok_http_webhook_777',
        ]);

        // Envío con firma alterada sobre un evento nuevo
        $badPayload = [
            'event_id'       => 'evt_http_corrupt_102',
            'order_token'    => 'tok_http_webhook_bad_888',
            'status'         => 'approved',
            'amount'         => 65.00,
            'transaction_id' => 'tx_http_888',
        ];
        $responseBad = $this->withHeaders([
            'X-Signature' => 'corrupted_signature',
        ])->postJson('/webhooks/payment/deuna', $badPayload);

        $responseBad->assertStatus(200);
        $responseBad->assertJson([
            'success' => false,
            'status'  => 'failed',
        ]);
    }

    public function test_live_mode_guard_blocks_all_gateway_actions(): void
    {
        config(['payment.mode' => 'live']);

        $gateway = app(DeunaGatewayAdapter::class);
        $order = $this->createTestOrder(50.00);

        // 1. Charge
        try {
            $gateway->charge($order);
            $this->fail('Se esperaba RuntimeException en charge live mode');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Modo LIVE no autorizado', $e->getMessage());
        }

        // 2. Query
        try {
            $gateway->queryTransaction('DEUNA-TOK-LIVE');
            $this->fail('Se esperaba RuntimeException en queryTransaction live mode');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Modo LIVE no autorizado', $e->getMessage());
        }

        // 3. Webhook
        try {
            $gateway->handleWebhook(['order_token' => 'TOK-LIVE']);
            $this->fail('Se esperaba RuntimeException en handleWebhook live mode');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Modo LIVE no autorizado', $e->getMessage());
        }
    }

    public function test_e2e_reconciliation_flow_with_deuna_payment(): void
    {
        $order = $this->createTestOrder(100.00);
        $orderToken = 'tok_e2e_deuna_reconcile';

        Http::fake([
            'https://api.sandbox.deuna.io/merchants/orders' => Http::response([
                'order_token'  => $orderToken,
                'checkout_url' => 'https://api.sandbox.deuna.io/checkout?order_token=' . $orderToken,
                'status'       => 'pending',
            ], 200),
        ]);

        $service = app(PaymentService::class);

        // 1. Iniciar cobro con DEUNA
        $initResult = $service->process($order, PaymentMethod::TestCard);
        $this->assertFalse($initResult['success']);
        $this->assertEquals('Redirigiendo a la pasarela de pago.', $initResult['message']);

        $payment = Payment::query()->where('order_id', $order->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals(PaymentStatus::Pending, $payment->status);
        $this->assertEquals($orderToken, $payment->reference);

        // 2. Simular llegada de Webhook firmado desde DEUNA
        $webhookPayload = [
            'event_id'       => 'evt_deuna_9999',
            'order_token'    => $orderToken,
            'status'         => 'paid',
            'amount'         => (float) $order->total,
            'transaction_id' => 'tx_confirmed_9999',
        ];
        $rawPayload = json_encode($webhookPayload);

        $client = app(DeunaClient::class);
        $signature = $client->computeWebhookSignature($rawPayload, $this->webhookSecret);

        $response = $this->withHeaders([
            'X-Signature' => $signature,
        ])->postJson('/webhooks/payment/deuna', $webhookPayload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'status'  => 'approved',
        ]);

        // 3. Reconciliación exitosa del pago y del pedido
        $payment->refresh();
        $this->assertEquals(PaymentStatus::Approved, $payment->status);

        $order->refresh();
        $this->assertEquals(OrderStatus::Paid, $order->status);
        $this->assertNotNull($order->paid_at);
    }
}
