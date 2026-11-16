<?php

namespace App\Payments\Gateways;

use App\Contracts\PaymentGatewayInterface;
use App\DTOs\PaymentProcessResult;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Payments\Clients\DeunaClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class DeunaGatewayAdapter implements PaymentGatewayInterface
{
    private DeunaClient $client;

    public function __construct(?DeunaClient $client = null)
    {
        $this->client = $client ?? app(DeunaClient::class);
    }

    public function getClient(): DeunaClient
    {
        return $this->client;
    }

    public function getIdentifier(): string
    {
        return 'deuna';
    }

    public function getName(): string
    {
        return 'DEUNA';
    }

    public function getMode(): string
    {
        return config('payment.mode', 'mock');
    }

    public function charge(Order $order, array $payload = []): PaymentProcessResult
    {
        $mode = $this->getMode();

        if ($mode === 'live') {
            throw new RuntimeException('Modo LIVE no autorizado');
        }

        $this->warnIfMissingCredentials();

        $idempotencyKey = $payload['idempotency_key'] ?? Str::uuid()->toString();
        $reference      = 'DEUNA-' . strtoupper(Str::random(10));
        $amount         = (float) $order->total;
        $baseUrl        = config('payment.deuna.sandbox_url', 'https://api.sandbox.deuna.io');

        try {
            $response = $this->client->createOrder($order, $idempotencyKey, $payload);

            $orderToken = $response['order_token']
                ?? $response['token']
                ?? $response['order']['order_token']
                ?? $response['order']['token']
                ?? $reference;

            $checkoutUrl = $response['checkout_url']
                ?? $response['order']['checkout_url']
                ?? $response['redirect_url']
                ?? ($baseUrl . '/checkout?order_token=' . $orderToken);

            return PaymentProcessResult::redirect(
                reference:             $orderToken,
                amount:                $amount,
                redirectUrl:           $checkoutUrl,
                externalTransactionId: $orderToken,
                rawResponse:           $response,
                idempotencyKey:        $idempotencyKey,
            );
        } catch (\Throwable $e) {
            if (empty(config('payment.deuna.api_key'))) {
                $redirectUrl = $baseUrl . '/checkout?order_token=' . $reference . '&idempotency=' . $idempotencyKey;

                return PaymentProcessResult::redirect(
                    reference:             $reference,
                    amount:                $amount,
                    redirectUrl:           $redirectUrl,
                    externalTransactionId: $reference,
                    rawResponse: [
                        'gateway'     => 'deuna',
                        'mode'        => 'sandbox',
                        'order_token' => $reference,
                        'amount'      => $amount,
                        'currency'    => 'USD',
                        'idempotency' => $idempotencyKey,
                        'note'        => 'SANDBOX STUB — credenciales no configuradas',
                    ],
                    idempotencyKey: $idempotencyKey,
                );
            }

            throw $e;
        }
    }

    public function queryTransaction(string $transactionReference): PaymentProcessResult
    {
        $mode = $this->getMode();

        if ($mode === 'live') {
            throw new RuntimeException('Modo LIVE no autorizado');
        }

        try {
            $data = $this->client->getOrder($transactionReference);

            $status = strtolower($data['status'] ?? $data['order']['status'] ?? $data['order_status'] ?? 'pending');
            $rawAmount = $data['amount'] ?? $data['order']['amount'] ?? $data['total'] ?? $data['order']['total'] ?? null;
            if ($rawAmount === null && isset($data['total_amount'])) {
                $rawAmount = $data['total_amount'] / 100;
            } elseif ($rawAmount === null && isset($data['order']['total_amount'])) {
                $rawAmount = $data['order']['total_amount'] / 100;
            }
            $amount = (float) ($rawAmount ?? 0.0);

            $extId = $data['transaction_id'] ?? $data['order']['transaction_id'] ?? $transactionReference;

            if (in_array($status, ['approved', 'completed', 'paid', 'success'], true)) {
                return PaymentProcessResult::approved(
                    reference:             $transactionReference,
                    amount:                $amount,
                    externalTransactionId: $extId,
                    message:               'Transacción aprobada en DEUNA.',
                    rawResponse:           $data,
                );
            }

            if (in_array($status, ['rejected', 'failed', 'declined', 'cancelled'], true)) {
                return PaymentProcessResult::failed(
                    reference:             $transactionReference,
                    amount:                $amount,
                    message:               'Transacción rechazada en DEUNA: ' . $status,
                    status:                PaymentStatus::Failed,
                    externalTransactionId: $extId,
                    rawResponse:           $data,
                );
            }

            return new PaymentProcessResult(
                success:               false,
                status:                PaymentStatus::Pending,
                reference:             $transactionReference,
                amount:                $amount,
                externalTransactionId: $extId,
                currency:              config('payment.currency', 'USD'),
                message:               'Transacción pendiente en DEUNA.',
                rawResponse:           $data,
            );
        } catch (\Throwable $e) {
            if (empty(config('payment.deuna.api_key'))) {
                return new PaymentProcessResult(
                    success:               false,
                    status:                PaymentStatus::Pending,
                    reference:             $transactionReference,
                    amount:                0.0,
                    externalTransactionId: $transactionReference,
                    currency:              'USD',
                    message:               'Consulta sandbox DEUNA (stub) — estado pendiente.',
                    rawResponse:           ['gateway' => 'deuna', 'mode' => 'sandbox', 'query' => true],
                );
            }

            throw $e;
        }
    }

    public function handleWebhook(array $payload, array $headers = []): PaymentProcessResult
    {
        $mode = $this->getMode();

        if ($mode === 'live') {
            throw new RuntimeException('Modo LIVE no autorizado');
        }

        $secret = config('payment.deuna.webhook_secret') ?: config('payment.webhook_secret', '');
        $signature = $headers['x-signature']
            ?? $headers['X-Signature']
            ?? $headers['x-deuna-signature']
            ?? $headers['X-Deuna-Signature']
            ?? null;

        $reference = $payload['order_token']
            ?? $payload['order']['order_token']
            ?? $payload['order']['token']
            ?? $payload['token']
            ?? 'DEUNA-UNKNOWN';

        $amount = (float) ($payload['amount']
            ?? $payload['total']
            ?? (isset($payload['total_amount']) ? $payload['total_amount'] / 100 : 0.0)
            ?? (isset($payload['order']['total_amount']) ? $payload['order']['total_amount'] / 100 : 0.0));

        // Validar firma HMAC-SHA256 Base64 si se dispone del secreto o si se envió firma
        if (!empty($secret) || !empty($signature)) {
            if (empty($secret) || empty($signature)) {
                return PaymentProcessResult::failed(
                    reference:   $reference,
                    amount:      $amount,
                    message:     'Webhook DEUNA: firma inválida.',
                    status:      PaymentStatus::Failed,
                    rawResponse: $payload,
                );
            }

            $rawPayload = null;
            if (isset($payload['_raw_payload']) && is_string($payload['_raw_payload'])) {
                $rawPayload = $payload['_raw_payload'];
            } elseif (request() && !empty(request()->getContent())) {
                $rawPayload = request()->getContent();
            }

            $isValid = false;
            if ($rawPayload && $this->client->verifyWebhookSignature($rawPayload, $signature, $secret)) {
                $isValid = true;
            } else {
                $encoded = json_encode($payload);
                if ($encoded && $this->client->verifyWebhookSignature($encoded, $signature, $secret)) {
                    $isValid = true;
                } else {
                    $encodedUnescaped = json_encode($payload, JSON_UNESCAPED_SLASHES);
                    if ($encodedUnescaped && $this->client->verifyWebhookSignature($encodedUnescaped, $signature, $secret)) {
                        $isValid = true;
                    }
                }
            }

            if (!$isValid) {
                return PaymentProcessResult::failed(
                    reference:   $reference,
                    amount:      $amount,
                    message:     'Webhook DEUNA: firma inválida.',
                    status:      PaymentStatus::Failed,
                    rawResponse: $payload,
                );
            }
        }

        $status = strtolower($payload['status'] ?? $payload['order_status'] ?? $payload['order']['status'] ?? 'pending');
        $externalId = $payload['transaction_id']
            ?? $payload['order']['transaction_id']
            ?? $payload['order_token']
            ?? $reference;

        if (in_array($status, ['completed', 'approved', 'paid', 'success'], true)) {
            return PaymentProcessResult::approved(
                reference:             $reference,
                amount:                $amount,
                externalTransactionId: $externalId,
                message:               'Webhook DEUNA sandbox: aprobado.',
                rawResponse:           $payload,
            );
        }

        return PaymentProcessResult::failed(
            reference:             $reference,
            amount:                $amount,
            message:               'Webhook DEUNA sandbox: ' . $status,
            status:                PaymentStatus::Failed,
            externalTransactionId: $externalId,
            rawResponse:           $payload,
        );
    }

    private function warnIfMissingCredentials(): void
    {
        if (empty(config('payment.deuna.api_key'))) {
            Log::warning('DeunaGatewayAdapter: DEUNA_API_KEY no configurado.');
        }
    }
}
