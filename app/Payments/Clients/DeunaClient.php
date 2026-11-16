<?php

namespace App\Payments\Clients;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class DeunaClient
{
    private string $baseUrl;
    private string $apiKey;
    private string $storeCode;
    private string $webhookSecret;

    public function __construct(
        ?string $apiKey = null,
        ?string $storeCode = null,
        ?string $baseUrl = null,
        ?string $webhookSecret = null,
    ) {
        $mode = config('payment.mode', 'mock');
        $this->baseUrl = $baseUrl ?? ($mode === 'live'
            ? config('payment.deuna.live_url', 'https://api.deuna.com')
            : config('payment.deuna.sandbox_url', 'https://api-staging.deuna.com'));

        $this->apiKey = $apiKey ?? (string) config('payment.deuna.api_key', '');
        $this->storeCode = $storeCode ?? (string) (config('payment.deuna.store_code') ?: config('payment.deuna.store_id', ''));
        $this->webhookSecret = $webhookSecret ?? (string) (config('payment.deuna.webhook_secret') ?: config('payment.webhook_secret', ''));
    }

    /**
     * Valida que no se intente operar en modo LIVE sin autorización.
     */
    public function guardMode(): void
    {
        if (config('payment.mode') === 'live') {
            throw new RuntimeException('Modo LIVE no autorizado');
        }
    }

    /**
     * Construye las cabeceras estándar para DEUNA.
     *
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return [
            'X-Api-Key'    => $this->apiKey,
            'X-Store-Code' => $this->storeCode,
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ];
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function getStoreCode(): string
    {
        return $this->storeCode;
    }

    public function getWebhookSecret(): string
    {
        return $this->webhookSecret;
    }

    /**
     * Formatea el payload de la orden según especificaciones oficiales de DEUNA.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function formatOrderPayload(Order $order, string $idempotencyKey, array $options = []): array
    {
        $items = [];
        if ($order->relationLoaded('items') || method_exists($order, 'items')) {
            foreach ($order->items as $item) {
                $unitPrice = (float) ($item->unit_price ?? 0);
                $qty = (int) ($item->quantity ?? 1);
                $lineTotal = (float) ($item->line_total ?? ($unitPrice * $qty));

                $items[] = [
                    'id'           => (string) ($item->product_id ?? $item->id ?? Str::uuid()->toString()),
                    'name'         => (string) ($item->product_name ?? 'Producto'),
                    'description'  => (string) ($item->product_name ?? 'Producto'),
                    'quantity'     => $qty,
                    'unit_price'   => [
                        'amount'   => (int) round($unitPrice * 100),
                        'currency' => config('payment.currency', 'USD'),
                    ],
                    'total_amount' => [
                        'amount'   => (int) round($lineTotal * 100),
                        'currency' => config('payment.currency', 'USD'),
                    ],
                ];
            }
        }

        $subtotal = (float) ($order->subtotal ?? $order->total ?? 0);
        $tax = (float) ($order->tax_amount ?? 0);
        $shipping = (float) ($order->shipping_amount ?? 0);
        $total = (float) ($order->total ?? ($subtotal + $tax + $shipping));
        $orderId = (string) ($order->order_number ?? $order->id ?? Str::uuid()->toString());

        $payload = [
            'order' => [
                'order_id'        => $orderId,
                'currency'        => config('payment.currency', 'USD'),
                'tax_amount'      => (int) round($tax * 100),
                'shipping_amount' => (int) round($shipping * 100),
                'sub_total'       => (int) round($subtotal * 100),
                'total_amount'    => (int) round($total * 100),
                'items'           => $items,
            ],
            'idempotency_key' => $idempotencyKey,
        ];

        if (!empty($order->customer_name) || !empty($order->phone) || $order->user) {
            $user = $order->user;
            $payload['order']['user'] = [
                'id'         => (string) ($order->user_id ?? $user?->id ?? ''),
                'email'      => (string) ($user?->email ?? $options['email'] ?? 'customer@zyro.local'),
                'first_name' => (string) ($order->customer_name ?? $user?->name ?? 'Cliente'),
                'phone'      => (string) ($order->phone ?? ''),
            ];
        }

        if (!empty($order->address) || !empty($order->city)) {
            $payload['order']['shipping_address'] = [
                'address1' => (string) ($order->address ?? ''),
                'city'     => (string) ($order->city ?? ''),
                'country'  => 'EC',
            ];
        }

        if (!empty($options['extra_payload']) && is_array($options['extra_payload'])) {
            $payload = array_merge($payload, $options['extra_payload']);
        }

        return $payload;
    }

    /**
     * Crea una orden en DEUNA a través de POST /merchants/orders.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function createOrder(Order $order, string $idempotencyKey, array $options = []): array
    {
        $this->guardMode();

        $url = rtrim($this->baseUrl, '/') . '/merchants/orders';
        $payload = $this->formatOrderPayload($order, $idempotencyKey, $options);

        $response = Http::withHeaders($this->getHeaders())
            ->post($url, $payload);

        if ($response->failed()) {
            Log::error('DEUNA createOrder falló', [
                'status'   => $response->status(),
                'order_id' => $order->id,
                'error'    => $response->json('message') ?? $response->body(),
            ]);

            throw new RuntimeException("Error en DEUNA API createOrder [{$response->status()}]: {$response->body()}");
        }

        return $response->json() ?? [];
    }

    /**
     * Consulta el estado de una orden en DEUNA a través de GET /merchants/orders/{orderToken}.
     *
     * @return array<string, mixed>
     */
    public function getOrder(string $orderToken): array
    {
        $this->guardMode();

        $url = rtrim($this->baseUrl, '/') . '/merchants/orders/' . urlencode($orderToken);

        $response = Http::withHeaders($this->getHeaders())
            ->get($url);

        if ($response->failed()) {
            Log::error('DEUNA getOrder falló', [
                'status'      => $response->status(),
                'order_token' => $orderToken,
                'error'       => $response->json('message') ?? $response->body(),
            ]);

            throw new RuntimeException("Error en DEUNA API getOrder [{$response->status()}]: {$response->body()}");
        }

        return $response->json() ?? [];
    }

    /**
     * Calcula la firma HMAC-SHA256 codificada en Base64 según especificación oficial de DEUNA.
     */
    public function computeWebhookSignature(string $rawPayload, string $secret): string
    {
        return base64_encode(hash_hmac('sha256', $rawPayload, $secret, true));
    }

    /**
     * Valida criptográficamente la firma del webhook con Base64 HMAC-SHA256 usando hash_equals().
     */
    public function verifyWebhookSignature(string $rawPayload, string $signature, string $secret): bool
    {
        if ($rawPayload === '' || $signature === '' || $secret === '') {
            return false;
        }

        $expected = $this->computeWebhookSignature($rawPayload, $secret);

        return hash_equals($expected, trim($signature));
    }
}
