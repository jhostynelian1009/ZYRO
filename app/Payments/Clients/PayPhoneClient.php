<?php

namespace App\Payments\Clients;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * PayPhoneClient — Cliente HTTP oficial para el API de PayPhone Ecuador.
 *
 * Autenticación:
 * - Bearer Token (Authorization: Bearer <PAYPHONE_TOKEN>)
 * - Store ID (PAYPHONE_STORE_ID)
 *
 * Flujo:
 * - preparePayment(): POST /button/Prepare
 * - confirmTransaction(): POST /button/Confirm
 * - queryTransaction(): GET /button/V2/Confirm/{id}
 */
class PayPhoneClient
{
    private string $token;
    private string $storeId;
    private string $baseUrl;
    private string $mode;

    public function __construct(
        ?string $token = null,
        ?string $storeId = null,
        ?string $baseUrl = null,
        ?string $mode = null,
    ) {
        $this->mode = $mode ?? (string) config('payment.mode', 'mock');
        $this->token = $token ?? (string) config('payment.payphone.token', '');
        $this->storeId = $storeId ?? (string) config('payment.payphone.store_id', '');
        $this->baseUrl = rtrim($baseUrl ?? (string) (
            $this->mode === 'live'
                ? config('payment.payphone.live_url', 'https://pay.payphonetodoesposible.com/api')
                : config('payment.payphone.sandbox_url', 'https://pay.payphonetodoesposible.com/api')
        ), '/');
    }

    /**
     * Valida que no se intente operar en modo LIVE sin autorización expresa.
     */
    public function guardMode(): void
    {
        $currentMode = config('payment.mode', $this->mode);
        if ($currentMode === 'live' || $this->mode === 'live') {
            throw new RuntimeException('Modo LIVE no autorizado');
        }
    }

    /**
     * Cabeceras oficiales requeridas por el API de PayPhone.
     *
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->token,
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ];
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getStoreId(): string
    {
        return $this->storeId;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function getMode(): string
    {
        return $this->mode;
    }

    /**
     * Prepara una transacción de cobro (botón de pago) en PayPhone.
     * POST /button/Prepare
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function preparePayment(Order $order, string $idempotencyKey, array $options = []): array
    {
        $this->guardMode();

        $subtotal = (float) ($order->subtotal ?? 0);
        $tax = (float) ($order->tax_amount ?? 0);
        $shipping = (float) ($order->shipping_amount ?? 0);
        $total = (float) ($order->total ?? ($subtotal + $tax + $shipping));

        $totalCents    = (int) round($total * 100);
        $taxCents      = (int) round($tax * 100);
        $subtotalCents = (int) round($subtotal * 100);

        // Regla oficial PayPhone: amount = amountWithTax + amountWithoutTax + tax + service + tip
        if ($taxCents > 0) {
            $amountWithTax = $subtotalCents;
            $amountWithoutTax = $totalCents - $amountWithTax - $taxCents;
            if ($amountWithoutTax < 0) {
                $amountWithTax = max(0, $totalCents - $taxCents);
                $amountWithoutTax = 0;
            }
        } else {
            $amountWithTax = 0;
            $amountWithoutTax = $totalCents;
        }

        $payload = [
            'amount'              => (int) ($options['amount'] ?? $totalCents),
            'amountWithoutTax'    => (int) ($options['amountWithoutTax'] ?? $amountWithoutTax),
            'amountWithTax'       => (int) ($options['amountWithTax'] ?? $amountWithTax),
            'tax'                 => (int) ($options['tax'] ?? $taxCents),
            'service'             => (int) ($options['service'] ?? 0),
            'tip'                 => (int) ($options['tip'] ?? 0),
            'clientTransactionId' => $idempotencyKey,
            'storeId'             => $this->storeId,
            'currency'            => (string) ($options['currency'] ?? config('payment.currency', 'USD')),
            'reference'           => (string) ($options['reference'] ?? ('Pedido #' . ($order->order_number ?? $order->id ?? 'ZYRO'))),
        ];

        if (!empty($options['responseUrl'])) {
            $payload['responseUrl'] = $options['responseUrl'];
        }
        if (!empty($options['cancellationUrl'])) {
            $payload['cancellationUrl'] = $options['cancellationUrl'];
        }

        // Si no hay token configurado y Http no está simulado (stub sandbox), retornar fallback
        if (empty($this->token) && !$this->isHttpFaked()) {
            Log::warning('PayPhoneClient: PAYPHONE_TOKEN no configurado en sandbox, retornando stub ficticio.');
            $mockPaymentId = 'PP-' . strtoupper(Str::random(10));
            return [
                'paymentId'           => $mockPaymentId,
                'payWithCard'         => rtrim($this->baseUrl, '/') . '/button/checkout?paymentId=' . $mockPaymentId . '&clientTransactionId=' . $idempotencyKey,
                'clientTransactionId' => $idempotencyKey,
            ];
        }

        $url = rtrim($this->baseUrl, '/') . '/button/Prepare';

        Log::info('PayPhone preparePayment: enviando solicitud', [
            'clientTransactionId' => $idempotencyKey,
            'amount'              => $payload['amount'],
            'currency'            => $payload['currency'],
            'storeId'             => $this->storeId,
        ]);

        $response = Http::withHeaders($this->getHeaders())
            ->post($url, $payload);

        if ($response->failed()) {
            Log::error('PayPhone preparePayment falló', [
                'status'              => $response->status(),
                'clientTransactionId' => $idempotencyKey,
                'error'               => $response->json('message') ?? $response->body(),
            ]);

            throw new RuntimeException("Error en PayPhone API preparePayment [{$response->status()}]: {$response->body()}");
        }

        return $response->json() ?? [];
    }

    /**
     * Confirma una transacción ante PayPhone vía API oficial con Bearer Token.
     * POST /button/Confirm
     *
     * @return array<string, mixed>
     */
    public function confirmTransaction(string $transactionId, string $clientTxId): array
    {
        $this->guardMode();

        if (empty($this->token) && !$this->isHttpFaked()) {
            Log::warning('PayPhoneClient: PAYPHONE_TOKEN no configurado en sandbox, retornando confirmación stub.');
            return [
                'transactionId'       => $transactionId,
                'clientTransactionId' => $clientTxId,
                'transactionStatus'   => 'Approved',
                'statusCode'          => 3,
                'amount'              => 0,
            ];
        }

        $url = rtrim($this->baseUrl, '/') . '/button/Confirm';

        $payload = [
            'id'         => is_numeric($transactionId) ? (int) $transactionId : $transactionId,
            'clientTxId' => $clientTxId,
        ];

        Log::info('PayPhone confirmTransaction: enviando confirmación', [
            'transactionId' => $transactionId,
            'clientTxId'    => $clientTxId,
        ]);

        $response = Http::withHeaders($this->getHeaders())
            ->post($url, $payload);

        if ($response->failed()) {
            Log::error('PayPhone confirmTransaction falló', [
                'status'        => $response->status(),
                'transactionId' => $transactionId,
                'clientTxId'    => $clientTxId,
                'error'         => $response->json('message') ?? $response->body(),
            ]);

            $json = $response->json() ?? [];
            if (!isset($json['transactionStatus'])) {
                $json['transactionStatus'] = 'Failed';
            }
            if (!isset($json['message'])) {
                $json['message'] = "Error [{$response->status()}] al confirmar con PayPhone: " . $response->body();
            }

            return $json;
        }

        return $response->json() ?? [];
    }

    /**
     * Consulta el estado de una transacción ante PayPhone para conciliación.
     * GET /button/V2/Confirm/{id}
     *
     * @return array<string, mixed>
     */
    public function queryTransaction(string $transactionId): array
    {
        $this->guardMode();

        if (empty($this->token) && !$this->isHttpFaked()) {
            Log::warning('PayPhoneClient: PAYPHONE_TOKEN no configurado en sandbox, retornando consulta stub.');
            return [
                'transactionId'     => $transactionId,
                'transactionStatus' => 'Pending',
                'statusCode'        => 1,
            ];
        }

        $url = rtrim($this->baseUrl, '/') . '/button/V2/Confirm/' . urlencode($transactionId);

        Log::info('PayPhone queryTransaction: consultando transacción', [
            'transactionId' => $transactionId,
        ]);

        $response = Http::withHeaders($this->getHeaders())
            ->get($url);

        if ($response->failed()) {
            Log::error('PayPhone queryTransaction falló', [
                'status'        => $response->status(),
                'transactionId' => $transactionId,
                'error'         => $response->json('message') ?? $response->body(),
            ]);

            $json = $response->json() ?? [];
            if (!isset($json['transactionStatus'])) {
                $json['transactionStatus'] = 'Failed';
            }

            return $json;
        }

        return $response->json() ?? [];
    }

    /**
     * Comprueba si Laravel Http::fake() está activo para pruebas.
     */
    public function isHttpFaked(): bool
    {
        try {
            $factory = Http::getFacadeRoot();
            $ref = new \ReflectionClass($factory);
            if ($ref->hasProperty('recording')) {
                $prop = $ref->getProperty('recording');
                return (bool) $prop->getValue($factory);
            }
        } catch (\Throwable) {
            // Ignorar errores de reflexión
        }

        return false;
    }
}
