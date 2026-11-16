<?php

namespace App\Payments\Clients;

use App\Models\Order;
use Illuminate\Http\Client\Response;
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
 * - confirmTransaction(): POST /button/V2/Confirm  { id, clientTxId }
 * - queryTransaction(): GET /button/V2/Confirm/{id}
 */
class PayPhoneClient
{
    private string $token;
    private string $storeId;
    private string $baseUrl;
    private string $mode;
    private string $responseUrl;

    public function __construct(
        ?string $token = null,
        ?string $storeId = null,
        ?string $baseUrl = null,
        ?string $mode = null,
    ) {
        $this->mode = $mode ?? (string) config('payment.mode', 'mock');
        $this->token = $this->normalizeToken($token ?? (string) config('payment.payphone.token', ''));
        $this->storeId = $storeId ?? (string) config('payment.payphone.store_id', '');
        $this->responseUrl = (string) config('payment.payphone.response_url', '');
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
            'Authorization' => 'Bearer '.$this->token,
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
            'reference'           => (string) ($options['reference'] ?? ('Pedido #'.($order->order_number ?? $order->id ?? 'ZYRO'))),
        ];

        $responseUrl = (string) ($options['responseUrl'] ?? $this->responseUrl);
        if ($responseUrl !== '') {
            $payload['responseUrl'] = $responseUrl;
        }
        if (! empty($options['cancellationUrl'])) {
            $payload['cancellationUrl'] = $options['cancellationUrl'];
        }

        if (empty($this->token) && ! $this->isHttpFaked()) {
            Log::warning('PayPhoneClient: PAYPHONE_TOKEN no configurado en sandbox, retornando stub ficticio.');
            $mockPaymentId = 'PP-'.strtoupper(Str::random(10));

            return [
                'paymentId'           => $mockPaymentId,
                'payWithCard'         => rtrim($this->baseUrl, '/').'/button/checkout?paymentId='.$mockPaymentId.'&clientTransactionId='.$idempotencyKey,
                'clientTransactionId' => $idempotencyKey,
            ];
        }

        $url = rtrim($this->baseUrl, '/').'/button/Prepare';

        Log::info('PayPhone preparePayment: enviando solicitud', [
            'clientTransactionId' => $idempotencyKey,
            'amount'              => $payload['amount'],
            'currency'            => $payload['currency'],
            'storeId_present'     => $this->storeId !== '',
            'response_url_present'=> $responseUrl !== '',
        ]);

        $response = Http::withHeaders($this->getHeaders())
            ->post($url, $payload);

        if ($response->failed()) {
            $this->logFailedResponse('preparePayment', $response, [
                'clientTransactionId' => $idempotencyKey,
            ]);

            throw new RuntimeException($this->formatApiError('preparePayment', $response));
        }

        return $response->json() ?? [];
    }

    /**
     * Confirma una transacción ante PayPhone vía API oficial.
     * POST /button/V2/Confirm  { id, clientTxId }
     *
     * @return array<string, mixed>
     */
    public function confirmTransaction(string $transactionId, string $clientTxId): array
    {
        $this->guardMode();

        if (empty($this->token) && ! $this->isHttpFaked()) {
            Log::warning('PayPhoneClient: PAYPHONE_TOKEN no configurado en sandbox, retornando confirmación stub.');

            return [
                'transactionId'       => $transactionId,
                'clientTransactionId' => $clientTxId,
                'transactionStatus'   => 'Approved',
                'statusCode'          => 3,
                'amount'              => 0,
            ];
        }

        $url = rtrim($this->baseUrl, '/').'/button/V2/Confirm';

        $payload = [
            'id'         => is_numeric($transactionId) ? (int) $transactionId : $transactionId,
            'clientTxId' => $clientTxId,
        ];

        Log::info('PayPhone confirmTransaction: enviando confirmación', [
            'transactionId' => $transactionId,
            'clientTxId'    => $clientTxId,
            'endpoint'      => $url,
        ]);

        $response = Http::withHeaders($this->getHeaders())
            ->post($url, $payload);

        if ($response->failed()) {
            $this->logFailedResponse('confirmTransaction', $response, [
                'transactionId' => $transactionId,
                'clientTxId'    => $clientTxId,
            ]);

            $json = $response->json() ?? [];
            if (! isset($json['transactionStatus'])) {
                $json['transactionStatus'] = 'Failed';
            }
            if (! isset($json['message'])) {
                $json['message'] = $this->formatApiError('confirmTransaction', $response);
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

        if (empty($this->token) && ! $this->isHttpFaked()) {
            Log::warning('PayPhoneClient: PAYPHONE_TOKEN no configurado en sandbox, retornando consulta stub.');

            return [
                'transactionId'     => $transactionId,
                'transactionStatus' => 'Pending',
                'statusCode'        => 1,
            ];
        }

        $url = rtrim($this->baseUrl, '/').'/button/V2/Confirm/'.urlencode($transactionId);

        Log::info('PayPhone queryTransaction: consultando transacción', [
            'transactionId' => $transactionId,
        ]);

        $response = Http::withHeaders($this->getHeaders())
            ->get($url);

        if ($response->failed()) {
            $this->logFailedResponse('queryTransaction', $response, [
                'transactionId' => $transactionId,
            ]);

            $json = $response->json() ?? [];
            if (! isset($json['transactionStatus'])) {
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

    private function normalizeToken(string $token): string
    {
        $token = trim($token);

        if (str_starts_with(strtolower($token), 'bearer ')) {
            return trim(substr($token, 7));
        }

        return $token;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function logFailedResponse(string $operation, Response $response, array $context): void
    {
        Log::error("PayPhone {$operation} falló", array_merge($context, [
            'status'     => $response->status(),
            'error_code' => $response->json('errorCode') ?? $response->json('ErrorCode'),
            'message'    => $response->json('message'),
            'request_id' => $this->extractRequestId($response),
        ]));
    }

    private function formatApiError(string $operation, Response $response): string
    {
        $errorCode = $response->json('errorCode') ?? $response->json('ErrorCode');
        $message = $response->json('message') ?? $response->body();
        $suffix = $errorCode !== null ? " ErrorCode={$errorCode}" : '';

        return "Error en PayPhone API {$operation} [{$response->status()}]: {$message}{$suffix}";
    }

    private function extractRequestId(Response $response): ?string
    {
        return $response->header('X-Request-Id')
            ?: $response->header('x-request-id')
            ?: $response->header('Request-Id')
            ?: null;
    }
}
