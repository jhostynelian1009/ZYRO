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
use App\Payments\Clients\PayPhoneClient;
use App\Payments\Gateways\PayPhoneGatewayAdapter;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class PayPhoneSandboxTest extends TestCase
{
    use RefreshDatabase;

    private string $token = 'test_payphone_token_sandbox_2026';
    private string $storeId = 'store-uuid-sandbox-9999';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'payment.mode'                 => 'sandbox',
            'payment.default_gateway'      => 'payphone',
            'payment.currency'             => 'USD',
            'payment.payphone.token'       => $this->token,
            'payment.payphone.store_id'    => $this->storeId,
            'payment.payphone.sandbox_url' => 'https://pay.payphonetodoesposible.com/api',
            'payment.payphone.live_url'    => 'https://pay.payphonetodoesposible.com/api',
        ]);
    }

    private function createTestOrder(float $amount = 100.00): Order
    {
        $customer = User::factory()->customer()->create([
            'name'  => 'Juan Pérez',
            'email' => 'juan.perez@zyro.test',
        ]);

        $product = Product::factory()->create([
            'name'  => 'Zapatillas Zyro Boost',
            'price' => $amount,
            'stock' => 5,
        ]);

        $order = Order::factory()->approved()->create([
            'order_number'    => 'ORD-PP-' . rand(1000, 9999),
            'user_id'         => $customer->id,
            'customer_name'   => 'Juan Pérez',
            'phone'           => '0998877665',
            'address'         => 'Av. 10 de Agosto y Orellana',
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

    public function test_successful_button_preparation_and_charge_redirect(): void
    {
        $order = $this->createTestOrder(100.00);
        $expectedPaymentId = 123456;
        $expectedPayWithCardUrl = 'https://pay.payphonetodoesposible.com/api/button/pay?id=' . $expectedPaymentId;

        Http::fake([
            'https://pay.payphonetodoesposible.com/api/button/Prepare' => Http::response([
                'paymentId'   => $expectedPaymentId,
                'payWithCard' => $expectedPayWithCardUrl,
            ], 200),
        ]);

        $gateway = app(PayPhoneGatewayAdapter::class);
        $idempotencyKey = (string) Str::uuid();

        $result = $gateway->charge($order, ['idempotency_key' => $idempotencyKey]);

        $this->assertTrue($result->requiresRedirect);
        $this->assertEquals(PaymentStatus::Pending, $result->status);
        $this->assertEquals($idempotencyKey, $result->reference);
        $this->assertEquals($expectedPayWithCardUrl, $result->redirectUrl);
        $this->assertEquals((string) $expectedPaymentId, $result->externalTransactionId);
        $this->assertEquals((float) $order->total, $result->amount);
        $this->assertEquals($idempotencyKey, $result->idempotencyKey);

        Http::assertSent(function (Request $request) use ($idempotencyKey) {
            $hasCorrectUrl = $request->url() === 'https://pay.payphonetodoesposible.com/api/button/Prepare';
            $hasBearer = $request->hasHeader('Authorization', 'Bearer ' . $this->token);
            $hasContentType = $request->header('Content-Type')[0] === 'application/json';

            $data = $request->data();
            $hasStoreId = $data['storeId'] === $this->storeId;
            $hasTxId = $data['clientTransactionId'] === $idempotencyKey;
            $hasCorrectAmount = $data['amount'] === 12000; // 100 * 1.15 + 5 = 120.00 => 12000 cents

            return $hasCorrectUrl && $hasBearer && $hasContentType && $hasStoreId && $hasTxId && $hasCorrectAmount;
        });
    }

    public function test_successful_webhook_confirmation_and_order_reconciliation(): void
    {
        $order = $this->createTestOrder(100.00);
        $txId = 765432;
        $clientTxId = (string) Str::uuid();

        // 1. Simular preparación de pago en PayPhone
        Http::fake([
            'https://pay.payphonetodoesposible.com/api/button/Prepare' => Http::response([
                'paymentId'   => $txId,
                'payWithCard' => 'https://pay.payphonetodoesposible.com/api/button/pay?id=' . $txId,
            ], 200),
            'https://pay.payphonetodoesposible.com/api/button/V2/Confirm' => Http::response([
                'email'               => 'juan.perez@zyro.test',
                'cardType'            => 'Visa',
                'bin'                 => '411111',
                'lastDigits'          => '1111',
                'amount'              => 12000,
                'clientTransactionId' => $clientTxId,
                'statusCode'          => 3,
                'transactionStatus'   => 'Approved',
                'authorizationCode'   => 'AUTH-765432',
                'transactionId'       => $txId,
            ], 200),
        ]);

        $service = app(PaymentService::class);
        $initResult = $service->process($order, PaymentMethod::TestCard, ['idempotency_key' => $clientTxId], 'payphone');

        $this->assertFalse($initResult['success']);
        $this->assertEquals('Redirigiendo a la pasarela de pago.', $initResult['message']);

        $payment = Payment::query()->where('order_id', $order->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals(PaymentStatus::Pending, $payment->status);
        $this->assertEquals($clientTxId, $payment->reference);
        $this->assertEquals((string) $txId, $payment->external_transaction_id);

        // 2. Simular llegada del webhook oficial de PayPhone
        $webhookPayload = [
            'id'         => $txId,
            'clientTxId' => $clientTxId,
        ];

        $response = $this->postJson('/webhooks/payment/payphone', $webhookPayload);

        $response->assertStatus(200);
        $response->assertJson([
            'success'   => true,
            'status'    => 'approved',
            'reference' => $clientTxId,
        ]);

        // 3. Verificar que se consultó POST /button/V2/Confirm con Bearer token
        Http::assertSent(function (Request $request) use ($txId, $clientTxId) {
            if ($request->url() !== 'https://pay.payphonetodoesposible.com/api/button/V2/Confirm') {
                return false;
            }

            $hasBearer = $request->hasHeader('Authorization', 'Bearer ' . $this->token);
            $data = $request->data();

            return $hasBearer && $data['id'] === $txId && $data['clientTxId'] === $clientTxId;
        });

        // 4. Verificar reconciliación: pago aprobado y pedido pagado
        $payment->refresh();
        $this->assertEquals(PaymentStatus::Approved, $payment->status);

        $order->refresh();
        $this->assertEquals(OrderStatus::Paid, $order->status);
        $this->assertNotNull($order->paid_at);
    }

    public function test_failed_webhook_confirmation_keeps_payment_and_order_unapproved(): void
    {
        $order = $this->createTestOrder(50.00);
        $txId = 987654;
        $clientTxId = (string) Str::uuid();

        Http::fake([
            'https://pay.payphonetodoesposible.com/api/button/Prepare' => Http::response([
                'paymentId'   => $txId,
                'payWithCard' => 'https://pay.payphonetodoesposible.com/api/button/pay?id=' . $txId,
            ], 200),
            'https://pay.payphonetodoesposible.com/api/button/V2/Confirm' => Http::response([
                'statusCode'          => 2,
                'transactionStatus'   => 'Canceled',
                'message'             => 'Transacción cancelada por el usuario',
                'transactionId'       => $txId,
                'clientTransactionId' => $clientTxId,
                'amount'              => 6250,
            ], 200),
        ]);

        $service = app(PaymentService::class);
        $service->process($order, PaymentMethod::TestCard, ['idempotency_key' => $clientTxId], 'payphone');

        $payment = Payment::query()->where('order_id', $order->id)->first();
        $this->assertEquals(PaymentStatus::Pending, $payment->status);

        // Webhook informando transacción cancelada
        $response = $this->postJson('/webhooks/payment/payphone', [
            'id'         => $txId,
            'clientTxId' => $clientTxId,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => false,
            'status'  => 'failed',
        ]);

        $payment->refresh();
        $this->assertEquals(PaymentStatus::Pending, $payment->status);

        $order->refresh();
        $this->assertNotEquals(OrderStatus::Paid, $order->status);
    }

    public function test_webhook_handles_payphone_api_http_error_gracefully(): void
    {
        Http::fake([
            'https://pay.payphonetodoesposible.com/api/button/V2/Confirm' => Http::response([
                'message' => 'Transacción no encontrada en PayPhone',
            ], 404),
        ]);

        $response = $this->postJson('/webhooks/payment/payphone', [
            'id'         => 999999,
            'clientTxId' => 'non-existent-client-tx',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => false,
            'status'  => 'failed',
        ]);
    }

    public function test_duplicate_webhook_is_idempotent(): void
    {
        $order = $this->createTestOrder(50.00);
        $txId = 333333;
        $clientTxId = (string) Str::uuid();

        Http::fake([
            'https://pay.payphonetodoesposible.com/api/button/Prepare' => Http::response([
                'paymentId'   => $txId,
                'payWithCard' => 'https://pay.payphonetodoesposible.com/api/button/pay?id=' . $txId,
            ], 200),
            'https://pay.payphonetodoesposible.com/api/button/V2/Confirm' => Http::response([
                'transactionId'       => $txId,
                'clientTransactionId' => $clientTxId,
                'statusCode'          => 3,
                'transactionStatus'   => 'Approved',
                'amount'              => 6250,
            ], 200),
        ]);

        $service = app(PaymentService::class);
        $service->process($order, PaymentMethod::TestCard, ['idempotency_key' => $clientTxId], 'payphone');

        $payload = [
            'id'         => $txId,
            'clientTxId' => $clientTxId,
        ];

        // Primer envío del webhook
        $resp1 = $this->postJson('/webhooks/payment/payphone', $payload);
        $resp1->assertStatus(200);
        $resp1->assertJson(['success' => true, 'status' => 'approved']);

        // Segundo envío del mismo webhook (idempotente)
        $resp2 = $this->postJson('/webhooks/payment/payphone', $payload);
        $resp2->assertStatus(200);
        $resp2->assertJson(['success' => true, 'status' => 'approved']);
    }

    public function test_live_mode_rejection_in_sandbox_test(): void
    {
        config(['payment.mode' => 'live']);

        $adapter = app(PayPhoneGatewayAdapter::class);
        $order = $this->createTestOrder(20.00);

        try {
            $adapter->charge($order);
            $this->fail('Se esperaba RuntimeException en charge live mode');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Modo LIVE no autorizado', $e->getMessage());
        }

        try {
            $adapter->queryTransaction('7777');
            $this->fail('Se esperaba RuntimeException en queryTransaction live mode');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Modo LIVE no autorizado', $e->getMessage());
        }

        try {
            $adapter->handleWebhook(['id' => 7777, 'clientTxId' => 'tx-live']);
            $this->fail('Se esperaba RuntimeException en handleWebhook live mode');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Modo LIVE no autorizado', $e->getMessage());
        }
    }

    public function test_query_transaction_status_approved(): void
    {
        $txId = '112233';

        Http::fake([
            "https://pay.payphonetodoesposible.com/api/button/V2/Confirm/{$txId}" => Http::response([
                'transactionId'       => 112233,
                'clientTransactionId' => 'client-tx-112233',
                'statusCode'          => 3,
                'transactionStatus'   => 'Approved',
                'amount'              => 8050,
            ], 200),
        ]);

        $gateway = app(PayPhoneGatewayAdapter::class);
        $result = $gateway->queryTransaction($txId);

        $this->assertTrue($result->success);
        $this->assertEquals(PaymentStatus::Approved, $result->status);
        $this->assertEquals($txId, $result->reference);
        $this->assertEquals(80.50, $result->amount);
        $this->assertEquals('112233', $result->externalTransactionId);
        $this->assertStringContainsString('aprobada', strtolower($result->message));
    }

    public function test_query_transaction_status_failed(): void
    {
        $txId = '445566';

        Http::fake([
            "https://pay.payphonetodoesposible.com/api/button/V2/Confirm/{$txId}" => Http::response([
                'transactionId'       => 445566,
                'clientTransactionId' => 'client-tx-445566',
                'statusCode'          => 2,
                'transactionStatus'   => 'Rejected',
                'amount'              => 5000,
            ], 200),
        ]);

        $gateway = app(PayPhoneGatewayAdapter::class);
        $result = $gateway->queryTransaction($txId);

        $this->assertFalse($result->success);
        $this->assertEquals(PaymentStatus::Failed, $result->status);
        $this->assertEquals($txId, $result->reference);
        $this->assertEquals('445566', $result->externalTransactionId);
        $this->assertStringContainsString('rejected', strtolower($result->message));
    }

    public function test_direct_adapter_handle_webhook_missing_params(): void
    {
        $gateway = app(PayPhoneGatewayAdapter::class);

        $result = $gateway->handleWebhook([]);

        $this->assertFalse($result->success);
        $this->assertEquals(PaymentStatus::Failed, $result->status);
        $this->assertStringContainsString('faltan parámetros requeridos', $result->message);
    }

    public function test_webhook_confirmation_fails_if_five_minute_limit_exceeded(): void
    {
        $order = $this->createTestOrder(75.00);
        $txId = 554433;
        $clientTxId = (string) Str::uuid();

        // PayPhone responde que la confirmación expiró por superar los 5 minutos
        Http::fake([
            'https://pay.payphonetodoesposible.com/api/button/Prepare' => Http::response([
                'paymentId'   => $txId,
                'payWithCard' => 'https://pay.payphonetodoesposible.com/api/button/pay?id=' . $txId,
            ], 200),
            'https://pay.payphonetodoesposible.com/api/button/V2/Confirm' => Http::response([
                'statusCode'          => 2,
                'transactionStatus'   => 'Expired',
                'message'             => 'Tiempo límite de confirmación excedido (5 minutos)',
                'transactionId'       => $txId,
                'clientTransactionId' => $clientTxId,
            ], 400),
        ]);

        $service = app(PaymentService::class);
        $service->process($order, PaymentMethod::TestCard, ['idempotency_key' => $clientTxId], 'payphone');

        $payment = Payment::query()->where('order_id', $order->id)->first();
        $this->assertEquals(PaymentStatus::Pending, $payment->status);

        $response = $this->postJson('/webhooks/payment/payphone', [
            'id'         => $txId,
            'clientTxId' => $clientTxId,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => false,
            'status'  => 'failed',
        ]);

        $payment->refresh();
        $this->assertNotEquals(PaymentStatus::Approved, $payment->status);

        $order->refresh();
        $this->assertNotEquals(OrderStatus::Paid, $order->status);
    }
}
