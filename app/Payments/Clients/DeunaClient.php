<?php

namespace App\Payments\Clients;

use App\Models\Order;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class DeunaClient
{
    private string $baseUrl;
    private string $apiKey;
    private string $storeCode;
    private string $merchantId;
    private string $webhookSecret;

    public function __construct(
        ?string $apiKey = null,
        ?string $storeCode = null,
        ?string $baseUrl = null,
        ?string $webhookSecret = null,
    ) {
        $mode = config('payment.mode', 'mock');
        $this->baseUrl = $baseUrl ?? ($mode === 'live'
            ? config('payment.deuna.live_url', 'https://api.deuna.io')
            : config('payment.deuna.sandbox_url', 'https://api.sandbox.deuna.io'));

        $this->apiKey = $apiKey ?? (string) (
            config('payment.deuna.api_key')
            ?: config('payment.deuna.private_key', '')
        );
        $this->storeCode = $storeCode ?? (string) (config('payment.deuna.store_code') ?: config('payment.deuna.store_id', ''));
        $this->merchantId = (string) config('payment.deuna.merchant_id', '');
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
     * Cabeceras oficiales DEUNA.
     * Autenticación: X-API-KEY (clave privada del comercio).
     * X-Store-Code se envía cuando el comercio lo tiene configurado.
     *
     * @return array<string, string>
     */
    public function getHeaders(?string $idempotencyKey = null): array
    {
        $headers = [
            'X-API-KEY'    => $this->apiKey,
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ];

        if ($this->storeCode !== '') {
            $headers['X-Store-Code'] = $this->storeCode;
        }

        if ($idempotencyKey) {
            $headers['X-Idempotency-Key'] = $idempotencyKey;
        }

        return $headers;
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
        $subtotalCents = (int) round($subtotal * 100);
        $taxCents = (int) round($tax * 100);
        $totalCents = (int) round($total * 100);

        $payload = [
            'order' => [
                'order_id'            => $orderId,
                'store_code'          => $this->storeCode !== '' ? $this->storeCode : 'all',
                'currency'            => config('payment.currency', 'USD'),
                'tax_amount'          => $taxCents,
                'shipping_amount'     => (int) round($shipping * 100),
                'items_total_amount'  => $subtotalCents,
                'sub_total'           => $subtotalCents,
                'total_amount'        => $totalCents,
                'items'               => $items,
            ],
            'idempotency_key' => $idempotencyKey,
        ];

        if ($this->merchantId !== '') {
            $payload['order']['merchant_id'] = $this->merchantId;
        }

        if (! empty($order->customer_name) || ! empty($order->phone) || $order->user) {
            $user = $order->user;
            $payload['order']['payer_info'] = [
                'email'            => (string) ($user?->email ?? $options['email'] ?? 'customer@zyro.local'),
                'external_user_id' => (string) ($order->user_id ?? $user?->id ?? ''),
            ];
            $payload['order']['user'] = [
                'id'         => (string) ($order->user_id ?? $user?->id ?? ''),
                'email'      => (string) ($user?->email ?? $options['email'] ?? 'customer@zyro.local'),
                'first_name' => (string) ($order->customer_name ?? $user?->name ?? 'Cliente'),
                'phone'      => (string) ($order->phone ?? ''),
            ];
        }

        if (! empty($order->address) || ! empty($order->city)) {
            $payload['order']['shipping_address'] = [
                'address1' => (string) ($order->address ?? ''),
                'city'     => (string) ($order->city ?? ''),
                'country'  => 'EC',
            ];
        }

        if (! empty($options['extra_payload']) && is_array($options['extra_payload'])) {
            $payload = array_merge($payload, $options['extra_payload']);
        }

        return $payload;
    }

    /**
     * Crea una orden tokenizada en DEUNA (flujo checkout / redirección).
     * POST /merchants/orders
     *
     * No se usa POST /v2/merchants/orders/purchase: ese endpoint es Direct API
     * y exige payment_source (token de tarjeta), lo que cambiaría el flujo comercial.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function createOrder(Order $order, string $idempotencyKey, array $options = []): array
    {
        $this->guardMode();

        $url = rtrim($this->baseUrl, '/').'/merchants/orders';
        $payload = $this->formatOrderPayload($order, $idempotencyKey, $options);

        $response = Http::withHeaders($this->getHeaders($idempotencyKey))
            ->post($url, $payload);

        if ($response->failed()) {
            $this->logFailedResponse('createOrder', $response, [
                'order_id' => $order->id,
            ]);

            throw new RuntimeException("Error en DEUNA API createOrder [{$response->status()}]: ".$this->safeErrorBody($response));
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

        $url = rtrim($this->baseUrl, '/').'/merchants/orders/'.urlencode($orderToken);

        $response = Http::withHeaders($this->getHeaders())
            ->get($url);

        if ($response->failed()) {
            $this->logFailedResponse('getOrder', $response, [
                'order_token' => $orderToken,
            ]);

            throw new RuntimeException("Error en DEUNA API getOrder [{$response->status()}]: ".$this->safeErrorBody($response));
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

    /**
     * @param  array<string, mixed>  $context
     */
    private function logFailedResponse(string $operation, Response $response, array $context): void
    {
        Log::error("DEUNA {$operation} falló", array_merge($context, [
            'status'     => $response->status(),
            'error'      => $response->json('message') ?? $response->json('error'),
            'request_id' => $response->header('X-Request-Id') ?: $response->header('x-request-id'),
        ]));
    }

    private function safeErrorBody(Response $response): string
    {
        $message = $response->json('message') ?? $response->json('error') ?? $response->body();

        return is_string($message) ? $message : (json_encode($message) ?: '');   
    }
}

