<?php

namespace Tests\Unit;

use App\DTOs\PaymentProcessResult;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Payments\Clients\DeunaClient;
use App\Payments\Gateways\DeunaGatewayAdapter;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class DeunaGatewayTest extends TestCase
{
    private DeunaClient $client;
    private string $apiKey = 'test_api_key_abc123';
    private string $storeCode = 'store_zyro_01';
    private string $webhookSecret = 'secret_webhook_xyz789';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'payment.mode'                 => 'sandbox',
            'payment.currency'             => 'USD',
            'payment.deuna.api_key'        => $this->apiKey,
            'payment.deuna.store_code'     => $this->storeCode,
            'payment.deuna.webhook_secret' => $this->webhookSecret,
            'payment.deuna.sandbox_url'    => 'https://api.sandbox.deuna.io',
            'payment.deuna.live_url'       => 'https://api.deuna.com',
        ]);

        $this->client = new DeunaClient(
            apiKey: $this->apiKey,
            storeCode: $this->storeCode,
            baseUrl: 'https://api.sandbox.deuna.io',
            webhookSecret: $this->webhookSecret
        );
    }

    public function test_headers_contain_api_key_and_store_code(): void
    {
        $headers = $this->client->getHeaders();

        $this->assertArrayHasKey('X-API-KEY', $headers);
        $this->assertArrayHasKey('X-Store-Code', $headers);
        $this->assertArrayHasKey('Content-Type', $headers);
        $this->assertArrayHasKey('Accept', $headers);

        $this->assertEquals($this->apiKey, $headers['X-API-KEY']);
        $this->assertEquals($this->storeCode, $headers['X-Store-Code']);
        $this->assertEquals('application/json', $headers['Content-Type']);
        $this->assertEquals('application/json', $headers['Accept']);
    }

    public function test_client_respects_custom_and_config_credentials(): void
    {
        $defaultClient = new DeunaClient();
        $this->assertEquals($this->apiKey, $defaultClient->getApiKey());
        $this->assertEquals($this->storeCode, $defaultClient->getStoreCode());
        $this->assertEquals($this->webhookSecret, $defaultClient->getWebhookSecret());
        $this->assertEquals('https://api.sandbox.deuna.io', $defaultClient->getBaseUrl());

        $customClient = new DeunaClient(
            apiKey: 'custom_key',
            storeCode: 'custom_store',
            baseUrl: 'https://custom.deuna.com',
            webhookSecret: 'custom_secret'
        );
        $this->assertEquals('custom_key', $customClient->getApiKey());
        $this->assertEquals('custom_store', $customClient->getStoreCode());
        $this->assertEquals('https://custom.deuna.com', $customClient->getBaseUrl());
        $this->assertEquals('custom_secret', $customClient->getWebhookSecret());
    }

    public function test_request_payload_formatting(): void
    {
        $order = new Order([
            'order_number'    => 'ORD-2026-TEST',
            'subtotal'        => 50.00,
            'tax_amount'      => 7.50,
            'shipping_amount' => 5.00,
            'total'           => 62.50,
            'customer_name'   => 'Carlos Santana',
            'phone'           => '0991234567',
            'address'         => 'Av. Amazonas y Naciones Unidas',
            'city'            => 'Quito',
        ]);
        $order->id = 101;

        $item1 = new OrderItem([
            'product_id'   => 1,
            'product_name' => 'Camiseta Zyro Premium',
            'quantity'     => 1,
            'unit_price'   => 30.00,
            'line_total'   => 30.00,
        ]);
        $item2 = new OrderItem([
            'product_id'   => 2,
            'product_name' => 'Gorra Zyro',
            'quantity'     => 1,
            'unit_price'   => 20.00,
            'line_total'   => 20.00,
        ]);
        $order->setRelation('items', collect([$item1, $item2]));

        $idempotencyKey = (string) Str::uuid();
        $payload = $this->client->formatOrderPayload($order, $idempotencyKey);

        $this->assertArrayHasKey('order', $payload);
        $this->assertArrayHasKey('idempotency_key', $payload);
        $this->assertEquals($idempotencyKey, $payload['idempotency_key']);

        $orderData = $payload['order'];
        $this->assertEquals('ORD-2026-TEST', $orderData['order_id']);
        $this->assertEquals($this->storeCode, $orderData['store_code']);
        $this->assertEquals('USD', $orderData['currency']);
        $this->assertEquals(5000, $orderData['sub_total']); // 50.00 * 100
        $this->assertEquals(750, $orderData['tax_amount']); // 7.50 * 100
        $this->assertEquals(500, $orderData['shipping_amount']); // 5.00 * 100
        $this->assertEquals(6250, $orderData['total_amount']); // 62.50 * 100

        $this->assertCount(2, $orderData['items']);
        $this->assertEquals('Camiseta Zyro Premium', $orderData['items'][0]['name']);
        $this->assertEquals(1, $orderData['items'][0]['quantity']);
        $this->assertEquals(3000, $orderData['items'][0]['unit_price']['amount']);
        $this->assertEquals(3000, $orderData['items'][0]['total_amount']['amount']);

        $this->assertEquals('Carlos Santana', $orderData['user']['first_name']);
        $this->assertEquals('0991234567', $orderData['user']['phone']);

        $this->assertEquals('Av. Amazonas y Naciones Unidas', $orderData['shipping_address']['address1']);
        $this->assertEquals('Quito', $orderData['shipping_address']['city']);
        $this->assertEquals('EC', $orderData['shipping_address']['country']);
    }

    public function test_base64_hmac_sha256_signature_calculation_and_validation(): void
    {
        $payload = json_encode([
            'event'       => 'order.paid',
            'order_token' => 'tok_live_abc123',
            'status'      => 'paid',
            'amount'      => 62.50,
        ]);

        $expectedSignature = base64_encode(hash_hmac('sha256', $payload, $this->webhookSecret, true));

        // Validación del método computeWebhookSignature
        $calculatedSignature = $this->client->computeWebhookSignature($payload, $this->webhookSecret);
        $this->assertEquals($expectedSignature, $calculatedSignature);

        // Validación de firma correcta
        $isValid = $this->client->verifyWebhookSignature($payload, $expectedSignature, $this->webhookSecret);
        $this->assertTrue($isValid);

        // Debe soportar espacios marginales en la cabecera
        $isValidWithSpaces = $this->client->verifyWebhookSignature($payload, "  {$expectedSignature}  \n", $this->webhookSecret);
        $this->assertTrue($isValidWithSpaces);
    }

    public function test_invalid_webhook_signature_rejection(): void
    {
        $payload = json_encode(['order_token' => 'tok_123', 'status' => 'paid']);

        // 1. Firma corrupta
        $this->assertFalse(
            $this->client->verifyWebhookSignature($payload, 'invalid-base64-signature', $this->webhookSecret)
        );

        // 2. Secreto incorrecto
        $correctSig = $this->client->computeWebhookSignature($payload, $this->webhookSecret);
        $this->assertFalse(
            $this->client->verifyWebhookSignature($payload, $correctSig, 'wrong_secret')
        );

        // 3. Payload alterado
        $tamperedPayload = json_encode(['order_token' => 'tok_123', 'status' => 'paid', 'tampered' => true]);
        $this->assertFalse(
            $this->client->verifyWebhookSignature($tamperedPayload, $correctSig, $this->webhookSecret)
        );

        // 4. Parámetros vacíos
        $this->assertFalse($this->client->verifyWebhookSignature('', $correctSig, $this->webhookSecret));
        $this->assertFalse($this->client->verifyWebhookSignature($payload, '', $this->webhookSecret));
        $this->assertFalse($this->client->verifyWebhookSignature($payload, $correctSig, ''));

        // 5. Validación en el adaptador DeunaGatewayAdapter
        $adapter = new DeunaGatewayAdapter($this->client);
        $webhookData = ['order_token' => 'tok_123', 'status' => 'approved', 'amount' => 10.0];

        $result = $adapter->handleWebhook($webhookData, ['X-Signature' => 'invalid-signature']);
        $this->assertFalse($result->success);
        $this->assertEquals(PaymentStatus::Failed, $result->status);
        $this->assertStringContainsString('firma inválida', $result->message);
    }

    public function test_live_mode_guard_blocks_operations(): void
    {
        config(['payment.mode' => 'live']);

        $adapter = new DeunaGatewayAdapter($this->client);

        // Guard en DeunaClient
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Modo LIVE no autorizado');
        $this->client->guardMode();
    }

    public function test_adapter_live_mode_guard_on_charge(): void
    {
        config(['payment.mode' => 'live']);
        $adapter = new DeunaGatewayAdapter($this->client);
        $order = new Order(['total' => 10.0]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Modo LIVE no autorizado');
        $adapter->charge($order);
    }

    public function test_adapter_live_mode_guard_on_query(): void
    {
        config(['payment.mode' => 'live']);
        $adapter = new DeunaGatewayAdapter($this->client);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Modo LIVE no autorizado');
        $adapter->queryTransaction('DEUNA-TOK-123');
    }

    public function test_adapter_live_mode_guard_on_webhook(): void
    {
        config(['payment.mode' => 'live']);
        $adapter = new DeunaGatewayAdapter($this->client);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Modo LIVE no autorizado');
        $adapter->handleWebhook(['order_token' => 'TOK-123']);
    }

    public function test_adapter_metadata_and_properties(): void
    {
        $adapter = new DeunaGatewayAdapter($this->client);

        $this->assertEquals('deuna', $adapter->getIdentifier());
        $this->assertEquals('DEUNA', $adapter->getName());
        $this->assertEquals('sandbox', $adapter->getMode());
        $this->assertSame($this->client, $adapter->getClient());
    }
}
