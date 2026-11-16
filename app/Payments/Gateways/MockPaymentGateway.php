<?php

namespace App\Payments\Gateways;

use App\Contracts\PaymentGatewayInterface;
use App\DTOs\PaymentProcessResult;
use App\Enums\PaymentStatus;
use App\Models\Order;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * MockPaymentGateway — exclusivo para desarrollo y pruebas.
 * Siempre aprueba (o rechaza según success_rate).
 * Lanza RuntimeException si se intenta usar en APP_ENV=production.
 */
class MockPaymentGateway implements PaymentGatewayInterface
{
    public function getIdentifier(): string
    {
        return 'mock';
    }

    public function getName(): string
    {
        return 'Mock Gateway (Solo Pruebas)';
    }

    public function getMode(): string
    {
        return 'mock';
    }

    public function charge(Order $order, array $payload = []): PaymentProcessResult
    {
        $this->guardAgainstProduction();

        $idempotencyKey = $payload['idempotency_key'] ?? Str::uuid()->toString();
        $reference      = 'MOCK-' . strtoupper(Str::random(10));
        $amount         = (float) $order->total;
        $successRate    = (float) config('payment.mock.success_rate', 1.0);
        $success        = (mt_rand(1, 100) / 100) <= $successRate;

        if ($success) {
            return PaymentProcessResult::approved(
                reference:             $reference,
                amount:                $amount,
                externalTransactionId: $reference,
                message:               'Pago simulado aprobado.',
                rawResponse:           ['gateway' => 'mock', 'status' => 'approved', 'reference' => $reference],
                idempotencyKey:        $idempotencyKey,
            );
        }

        return PaymentProcessResult::failed(
            reference:             $reference,
            amount:                $amount,
            message:               'Pago simulado rechazado (tasa de éxito configurada).',
            status:                PaymentStatus::Failed,
            externalTransactionId: $reference,
            rawResponse:           ['gateway' => 'mock', 'status' => 'failed', 'reference' => $reference],
            idempotencyKey:        $idempotencyKey,
        );
    }

    public function queryTransaction(string $transactionReference): PaymentProcessResult
    {
        $this->guardAgainstProduction();

        // En modo mock, una consulta de transacción siempre devuelve aprobado.
        return PaymentProcessResult::approved(
            reference:             $transactionReference,
            amount:                0.0,
            externalTransactionId: $transactionReference,
            message:               'Consulta mock: transacción aprobada.',
            rawResponse:           ['gateway' => 'mock', 'query' => true],
        );
    }

    public function handleWebhook(array $payload, array $headers = []): PaymentProcessResult
    {
        $this->guardAgainstProduction();

        $reference = $payload['reference'] ?? $payload['transaction_id'] ?? 'MOCK-WEBHOOK';
        $status    = $payload['status'] ?? 'approved';

        if ($status === 'approved') {
            return PaymentProcessResult::approved(
                reference:             $reference,
                amount:                (float) ($payload['amount'] ?? 0),
                externalTransactionId: $reference,
                message:               'Webhook mock procesado.',
                rawResponse:           $payload,
            );
        }

        return PaymentProcessResult::failed(
            reference: $reference,
            amount:    (float) ($payload['amount'] ?? 0),
            message:   'Webhook mock: pago no aprobado.',
            rawResponse: $payload,
        );
    }

    private function guardAgainstProduction(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException(
                'MockPaymentGateway no está permitido en entorno de producción. ' .
                'Configure PAYMENT_GATEWAY con una pasarela real.'
            );
        }
    }
}
