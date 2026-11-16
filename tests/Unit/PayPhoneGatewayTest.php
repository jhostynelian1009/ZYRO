<?php

namespace Tests\Unit;

use App\DTOs\PaymentProcessResult;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Payments\Clients\PayPhoneClient;
use App\Payments\Gateways\PayPhoneGatewayAdapter;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class PayPhoneGatewayTest extends TestCase
{
    private PayPhoneClient $client;
    private string $token = 'test_payphone_bearer_token_xyz987';
    private string $storeId = 'store-uuid-test-1234';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'payment.mode'                 => 'sandbox',
            'payment.currency'             => 'USD',
            'payment.payphone.token'       => $this->token,
            'payment.payphone.store_id'    => $this->storeId,
            'payment.payphone.sandbox_url' => 'https://pay.payphonetodoesposible.com/api',
            'payment.payphone.live_url'    => 'https://pay.payphonetodoesposible.com/api',
        ]);

        $this->client = new PayPhoneClient(
            token: $this->token,
            storeId: $this->storeId,
            baseUrl: 'https://pay.payphonetodoesposible.com/api',
            mode: 'sandbox'
        );
    }

    public function test_headers_contain_bearer_token_and_json_content_type(): void
    {
        $headers = $this->client->getHeaders();

        $this->assertArrayHasKey('Authorization', $headers);
        $this->assertArrayHasKey('Content-Type', $headers);
        $this->assertArrayHasKey('Accept', $headers);

        $this->assertEquals('Bearer ' . $this->token, $headers['Authorization']);
        $this->assertEquals('application/json', $headers['Content-Type']);
        $this->assertEquals('application/json', $headers['Accept']);
    }

    public function test_client_respects_custom_and_config_credentials(): void
    {
        $defaultClient = new PayPhoneClient();
        $this->assertEquals($this->token, $defaultClient->getToken());
        $this->assertEquals($this->storeId, $defaultClient->getStoreId());
        $this->assertEquals('https://pay.payphonetodoesposible.com/api', $defaultClient->getBaseUrl());
        $this->assertEquals('sandbox', $defaultClient->getMode());

        $customClient = new PayPhoneClient(
            token: 'custom_token_123',
            storeId: 'custom_store_456',
            baseUrl: 'https://custom.payphone.test/api',
            mode: 'sandbox'
        );
        $this->assertEquals('custom_token_123', $customClient->getToken());
        $this->assertEquals('custom_store_456', $customClient->getStoreId());
        $this->assertEquals('https://custom.payphone.test/api', $customClient->getBaseUrl());
    }

    public function test_prepare_payment_payload_formatting_and_cents_calculation(): void
    {
        $order = new Order([
            'order_number'    => 'ORD-PP-2026',
            'subtotal'        => 100.00,
            'tax_amount'      => 15.00,
            'shipping_amount' => 5.00,
            'total'           => 120.00,
        ]);
        $order->id = 10;

        $idempotencyKey = (string) Str::uuid();

        Http::fake([
            'https://pay.payphonetodoesposible.com/api/button/Prepare' => Http::response([
                'paymentId'   => 54321,
                'payWithCard' => 'https://pay.payphonetodoesposible.com/api/button/pay?id=54321',
            ], 200),
        ]);

        $response = $this->client->preparePayment($order, $idempotencyKey);

        $this->assertEquals(54321, $response['paymentId']);
        $this->assertArrayHasKey('payWithCard', $response);

        Http::assertSent(function (Request $request) use ($idempotencyKey) {
            $hasCorrectUrl = $request->url() === 'https://pay.payphonetodoesposible.com/api/button/Prepare';
            $hasBearer = $request->hasHeader('Authorization', 'Bearer ' . $this->token);
            $hasContentType = $request->header('Content-Type')[0] === 'application/json';

            $data = $request->data();
            $correctAmount = $data['amount'] === 12000;
            $correctAmountWithTax = $data['amountWithTax'] === 10000;
            $correctAmountWithoutTax = $data['amountWithoutTax'] === 500;
            $correctTax = $data['tax'] === 1500;
            $correctService = $data['service'] === 0;
            $correctTip = $data['tip'] === 0;
            $correctCurrency = $data['currency'] === 'USD';
            $correctStore = $data['storeId'] === $this->storeId;
            $correctTxId = $data['clientTransactionId'] === $idempotencyKey;
            $correctReference = $data['reference'] === 'Pedido #ORD-PP-2026';

            // PayPhone mathematical constraint: amount = amountWithTax + amountWithoutTax + tax + service + tip
            $mathMatches = ($data['amountWithTax'] + $data['amountWithoutTax'] + $data['tax'] + $data['service'] + $data['tip']) === $data['amount'];

            return $hasCorrectUrl && $hasBearer && $hasContentType &&
                $correctAmount && $correctAmountWithTax && $correctAmountWithoutTax &&
                $correctTax && $correctService && $correctTip && $correctCurrency &&
                $correctStore && $correctTxId && $correctReference && $mathMatches;
        });
    }

    public function test_prepare_payment_zero_tax_calculation(): void
    {
        $order = new Order([
            'order_number'    => 'ORD-PP-ZERO-TAX',
            'subtotal'        => 50.00,
            'tax_amount'      => 0.00,
            'shipping_amount' => 0.00,
            'total'           => 50.00,
        ]);
        $order->id = 11;

        $idempotencyKey = (string) Str::uuid();

        Http::fake([
            'https://pay.payphonetodoesposible.com/api/button/Prepare' => Http::response([
                'paymentId'   => 12345,
                'payWithCard' => 'https://pay.payphonetodoesposible.com/api/button/pay?id=12345',
            ], 200),
        ]);

        $this->client->preparePayment($order, $idempotencyKey);

        Http::assertSent(function (Request $request) {
            $data = $request->data();
            $mathMatches = ($data['amountWithTax'] + $data['amountWithoutTax'] + $data['tax'] + $data['service'] + $data['tip']) === $data['amount'];

            return $data['amount'] === 5000 &&
                $data['amountWithTax'] === 0 &&
                $data['amountWithoutTax'] === 5000 &&
                $data['tax'] === 0 &&
                $mathMatches;
        });
    }

    public function test_confirm_transaction_sends_bearer_token_and_correct_payload(): void
    {
        Http::fake([
            'https://pay.payphonetodoesposible.com/api/button/Confirm' => Http::response([
                'email'               => 'cliente@test.com',
                'cardType'            => 'Visa',
                'bin'                 => '411111',
                'lastDigits'          => '1111',
                'amount'              => 12000,
                'clientTransactionId' => 'client-tx-uuid-001',
                'statusCode'          => 3,
                'transactionStatus'   => 'Approved',
                'authorizationCode'   => 'AUTH-999',
                'transactionId'       => 54321,
            ], 200),
        ]);

        $result = $this->client->confirmTransaction('54321', 'client-tx-uuid-001');

        $this->assertEquals('Approved', $result['transactionStatus']);
        $this->assertEquals(3, $result['statusCode']);
        $this->assertEquals(54321, $result['transactionId']);

        Http::assertSent(function (Request $request) {
            $hasCorrectUrl = $request->url() === 'https://pay.payphonetodoesposible.com/api/button/Confirm';
            $hasBearer = $request->hasHeader('Authorization', 'Bearer ' . $this->token);
            $hasContentType = $request->header('Content-Type')[0] === 'application/json';

            $data = $request->data();
            $correctId = $data['id'] === 54321;
            $correctClientTxId = $data['clientTxId'] === 'client-tx-uuid-001';

            return $hasCorrectUrl && $hasBearer && $hasContentType && $correctId && $correctClientTxId;
        });
    }

    public function test_query_transaction_sends_bearer_token_and_correct_url(): void
    {
        $txId = '54321';

        Http::fake([
            "https://pay.payphonetodoesposible.com/api/button/V2/Confirm/{$txId}" => Http::response([
                'transactionId'       => 54321,
                'clientTransactionId' => 'client-tx-uuid-001',
                'transactionStatus'   => 'Approved',
                'statusCode'          => 3,
                'amount'              => 12000,
            ], 200),
        ]);

        $result = $this->client->queryTransaction($txId);

        $this->assertEquals('Approved', $result['transactionStatus']);
        $this->assertEquals(3, $result['statusCode']);

        Http::assertSent(function (Request $request) use ($txId) {
            $hasCorrectUrl = $request->url() === "https://pay.payphonetodoesposible.com/api/button/V2/Confirm/{$txId}";
            $hasBearer = $request->hasHeader('Authorization', 'Bearer ' . $this->token);

            return $hasCorrectUrl && $hasBearer && $request->method() === 'GET';
        });
    }

    public function test_live_mode_guard_blocks_client_operations(): void
    {
        config(['payment.mode' => 'live']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Modo LIVE no autorizado');
        $this->client->guardMode();
    }

    public function test_client_prepare_payment_throws_in_live_mode(): void
    {
        config(['payment.mode' => 'live']);
        $order = new Order(['total' => 10.0]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Modo LIVE no autorizado');
        $this->client->preparePayment($order, 'tx-1');
    }

    public function test_client_confirm_transaction_throws_in_live_mode(): void
    {
        config(['payment.mode' => 'live']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Modo LIVE no autorizado');
        $this->client->confirmTransaction('123', 'tx-1');
    }

    public function test_client_query_transaction_throws_in_live_mode(): void
    {
        config(['payment.mode' => 'live']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Modo LIVE no autorizado');
        $this->client->queryTransaction('123');
    }

    public function test_adapter_charge_throws_in_live_mode(): void
    {
        config(['payment.mode' => 'live']);
        $adapter = new PayPhoneGatewayAdapter($this->client);
        $order = new Order(['total' => 10.0]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Modo LIVE no autorizado');
        $adapter->charge($order);
    }

    public function test_adapter_query_throws_in_live_mode(): void
    {
        config(['payment.mode' => 'live']);
        $adapter = new PayPhoneGatewayAdapter($this->client);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Modo LIVE no autorizado');
        $adapter->queryTransaction('123');
    }

    public function test_adapter_webhook_throws_in_live_mode(): void
    {
        config(['payment.mode' => 'live']);
        $adapter = new PayPhoneGatewayAdapter($this->client);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Modo LIVE no autorizado');
        $adapter->handleWebhook(['id' => '123', 'clientTxId' => 'tx-1']);
    }

    public function test_client_throws_runtime_exception_on_api_prepare_error(): void
    {
        Http::fake([
            'https://pay.payphonetodoesposible.com/api/button/Prepare' => Http::response([
                'message' => 'Store inactive or not found',
            ], 400),
        ]);

        $order = new Order(['total' => 25.0]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('preparePayment [400]');
        $this->client->preparePayment($order, 'idemp-err');
    }

    public function test_adapter_metadata_and_properties(): void
    {
        $adapter = new PayPhoneGatewayAdapter($this->client);

        $this->assertEquals('payphone', $adapter->getIdentifier());
        $this->assertEquals('PayPhone', $adapter->getName());
        $this->assertEquals('sandbox', $adapter->getMode());
        $this->assertSame($this->client, $adapter->getClient());
    }

    public function test_adapter_charge_returns_redirect_dto(): void
    {
        $expectedRedirect = 'https://pay.payphonetodoesposible.com/api/button/pay?id=888';

        Http::fake([
            'https://pay.payphonetodoesposible.com/api/button/Prepare' => Http::response([
                'paymentId'   => 888,
                'payWithCard' => $expectedRedirect,
            ], 200),
        ]);

        $adapter = new PayPhoneGatewayAdapter($this->client);
        $order = new Order(['total' => 50.0]);
        $order->id = 5;

        $result = $adapter->charge($order, ['idempotency_key' => 'tx-uuid-888']);

        $this->assertTrue($result->requiresRedirect);
        $this->assertEquals(PaymentStatus::Pending, $result->status);
        $this->assertEquals($expectedRedirect, $result->redirectUrl);
        $this->assertEquals('tx-uuid-888', $result->reference);
        $this->assertEquals('888', $result->externalTransactionId);
        $this->assertEquals(50.0, $result->amount);
    }
}
