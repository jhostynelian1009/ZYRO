<?php

namespace App\Payments\Gateways;

use App\Contracts\PaymentGatewayInterface;
use App\DTOs\PaymentProcessResult;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Payments\Clients\PayPhoneClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * PayPhoneGatewayAdapter — Adaptador para la pasarela PayPhone Ecuador.
 *
 * Flujo oficial:
 * 1. charge(): Invoca preparePayment() para generar el botón de pago y retorna redirect.
 * 2. handleWebhook(): Recibe notificación con { id, clientTxId }, confirma la transacción
 *    con el API oficial POST /button/Confirm usando Bearer Token para autenticarla,
 *    y retorna resultado approved o failed.
 * 3. queryTransaction(): Consulta estado ante PayPhone para conciliación.
 *
 * Guardas de seguridad:
 * - Modo LIVE no autorizado: lanza RuntimeException.
 * - Credenciales seguras: NUNCA se registra el Bearer Token en logs.
 */
class PayPhoneGatewayAdapter implements PaymentGatewayInterface
{
    private PayPhoneClient $client;

    public function __construct(?PayPhoneClient $client = null)
    {
        $this->client = $client ?? app(PayPhoneClient::class);
    }

    public function getIdentifier(): string
    {
        return 'payphone';
    }

    public function getName(): string
    {
        return 'PayPhone';
    }

    public function getMode(): string
    {
        return config('payment.mode', 'mock');
    }

    public function getClient(): PayPhoneClient
    {
        return $this->client;
    }

    public function charge(Order $order, array $payload = []): PaymentProcessResult
    {
        $mode = $this->getMode();

        if ($mode === 'live') {
            throw new RuntimeException('Modo LIVE no autorizado');
        }

        $this->warnIfMissingCredentials();

        $idempotencyKey = $payload['idempotency_key'] ?? Str::uuid()->toString();
        $amount         = (float) $order->total;

        $response = $this->client->preparePayment($order, $idempotencyKey, $payload);

        $redirectUrl = $response['payWithCard'] ?? $response['redirectUrl'] ?? null;
        $paymentId   = isset($response['paymentId']) ? (string) $response['paymentId'] : null;

        if (!$redirectUrl) {
            $sandboxUrl  = rtrim(config('payment.payphone.sandbox_url', 'https://pay.payphonetodoesposible.com/api'), '/');
            $ref         = $paymentId ?? ('PP-' . strtoupper(Str::random(12)));
            $redirectUrl = $sandboxUrl . '/button/checkout?paymentId=' . $ref . '&clientTransactionId=' . $idempotencyKey;
        }

        return PaymentProcessResult::redirect(
            reference:             $idempotencyKey,
            amount:                $amount,
            redirectUrl:           $redirectUrl,
            externalTransactionId: $paymentId,
            rawResponse:           array_merge([
                'gateway'             => 'payphone',
                'mode'                => $mode,
                'clientTransactionId' => $idempotencyKey,
            ], $response),
            idempotencyKey:        $idempotencyKey,
        );
    }

    public function queryTransaction(string $transactionReference): PaymentProcessResult
    {
        $mode = $this->getMode();

        if ($mode === 'live') {
            throw new RuntimeException('Modo LIVE no autorizado');
        }

        $response = $this->client->queryTransaction($transactionReference);

        $statusStr = $response['transactionStatus'] ?? $response['status'] ?? 'pending';
        $amount    = isset($response['amount']) ? (float) ($response['amount'] / 100) : 0.0;
        $extId     = (string) ($response['transactionId'] ?? $transactionReference);

        if (strtolower((string) $statusStr) === 'approved' || ($response['statusCode'] ?? 0) === 3) {
            return PaymentProcessResult::approved(
                reference:             $transactionReference,
                amount:                $amount,
                externalTransactionId: $extId,
                message:               'Consulta PayPhone: transacción aprobada.',
                rawResponse:           $response,
                idempotencyKey:        $response['clientTransactionId'] ?? null,
            );
        }

        if (in_array(strtolower((string) $statusStr), ['failed', 'rejected', 'canceled', 'cancelled'], true)) {
            return PaymentProcessResult::failed(
                reference:             $transactionReference,
                amount:                $amount,
                message:               'Consulta PayPhone: transacción ' . $statusStr,
                status:                PaymentStatus::Failed,
                externalTransactionId: $extId,
                rawResponse:           $response,
                idempotencyKey:        $response['clientTransactionId'] ?? null,
            );
        }

        return new PaymentProcessResult(
            success:               false,
            status:                PaymentStatus::Pending,
            reference:             $transactionReference,
            amount:                $amount,
            externalTransactionId: $extId,
            currency:              'USD',
            message:               'Consulta sandbox PayPhone: transacción pendiente.',
            rawResponse:           $response,
            idempotencyKey:        $response['clientTransactionId'] ?? null,
        );
    }

    public function handleWebhook(array $payload, array $headers = []): PaymentProcessResult
    {
        $mode = $this->getMode();

        if ($mode === 'live') {
            throw new RuntimeException('Modo LIVE no autorizado');
        }

        // PayPhone notifica con id (identificador de transacción) y clientTxId
        $transactionId = (string) ($payload['id'] ?? $payload['transactionId'] ?? '');
        $clientTxId    = (string) ($payload['clientTxId'] ?? $payload['clientTransactionId'] ?? '');

        if (empty($transactionId) || empty($clientTxId)) {
            return PaymentProcessResult::failed(
                reference:   $clientTxId ?: 'PP-UNKNOWN',
                amount:      0.0,
                message:     'Webhook PayPhone: faltan parámetros requeridos (id o clientTxId).',
                status:      PaymentStatus::Failed,
                rawResponse: $payload,
            );
        }

        // Para garantizar autenticidad oficial, confirmamos la transacción ante PayPhone
        try {
            $confirmResult = $this->client->confirmTransaction($transactionId, $clientTxId);
        } catch (\Throwable $e) {
            Log::error('PayPhoneGatewayAdapter: error al confirmar transacción vía webhook', [
                'transactionId' => $transactionId,
                'clientTxId'    => $clientTxId,
                'error'         => $e->getMessage(),
            ]);

            return PaymentProcessResult::failed(
                reference:   $clientTxId,
                amount:      0.0,
                message:     'Error al confirmar transacción PayPhone: ' . $e->getMessage(),
                status:      PaymentStatus::Failed,
                rawResponse: array_merge($payload, ['error' => $e->getMessage()]),
            );
        }

        $status    = $confirmResult['transactionStatus'] ?? $confirmResult['status'] ?? null;
        $rawAmount = $confirmResult['amount'] ?? $payload['amount'] ?? 0;
        $amount    = (float) ($rawAmount > 0 ? $rawAmount / 100 : 0);
        $extId     = (string) ($confirmResult['transactionId'] ?? $transactionId);
        $reference = (string) ($confirmResult['clientTransactionId'] ?? $clientTxId);

        if (strtolower((string) $status) === 'approved' || ($confirmResult['statusCode'] ?? 0) === 3) {
            return PaymentProcessResult::approved(
                reference:             $reference,
                amount:                $amount,
                externalTransactionId: $extId,
                message:               'Pago confirmado y aprobado exitosamente por PayPhone.',
                rawResponse:           array_merge($payload, ['confirmation' => $confirmResult]),
                idempotencyKey:        $reference,
            );
        }

        $errorMessage = $confirmResult['message'] ?? ('Transacción PayPhone no aprobada (estado: ' . ($status ?? 'desconocido') . ').');

        return PaymentProcessResult::failed(
            reference:             $reference,
            amount:                $amount,
            message:               $errorMessage,
            status:                PaymentStatus::Failed,
            externalTransactionId: $extId,
            rawResponse:           array_merge($payload, ['confirmation' => $confirmResult]),
            idempotencyKey:        $reference,
        );
    }

    private function warnIfMissingCredentials(): void
    {
        if (empty(config('payment.payphone.token')) || empty(config('payment.payphone.store_id'))) {
            logger()->warning('PayPhoneGatewayAdapter: PAYPHONE_TOKEN o PAYPHONE_STORE_ID no configurados.');
        }
    }
}
