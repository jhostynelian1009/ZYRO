<?php

namespace App\Payments\Gateways;

use App\Contracts\PaymentGatewayInterface;
use App\DTOs\PaymentProcessResult;
use App\Enums\PaymentStatus;
use App\Models\Order;
use Illuminate\Support\Str;

/**
 * CashPaymentGateway — para ventas presenciales (POS).
 * Registra el cobro como aprobado de forma inmediata.
 * No realiza llamadas externas.
 */
class CashPaymentGateway implements PaymentGatewayInterface
{
    public function getIdentifier(): string
    {
        return 'cash';
    }

    public function getName(): string
    {
        return 'Pago en Efectivo (Presencial)';
    }

    public function getMode(): string
    {
        return config('payment.mode', 'mock');
    }

    public function charge(Order $order, array $payload = []): PaymentProcessResult
    {
        $idempotencyKey = $payload['idempotency_key'] ?? Str::uuid()->toString();
        $reference      = 'CASH-' . strtoupper(Str::random(8));
        $amount         = (float) $order->total;

        return PaymentProcessResult::approved(
            reference:             $reference,
            amount:                $amount,
            externalTransactionId: $reference,
            message:               'Pago en efectivo registrado por el operador.',
            rawResponse: [
                'gateway'    => 'cash',
                'status'     => 'approved',
                'reference'  => $reference,
                'operator'   => $payload['operator_id'] ?? null,
                'channel'    => $payload['channel'] ?? 'presencial',
            ],
            idempotencyKey: $idempotencyKey,
        );
    }

    public function queryTransaction(string $transactionReference): PaymentProcessResult
    {
        return PaymentProcessResult::approved(
            reference:             $transactionReference,
            amount:                0.0,
            externalTransactionId: $transactionReference,
            message:               'Pago en efectivo confirmado.',
            rawResponse:           ['gateway' => 'cash', 'query' => true],
        );
    }

    public function handleWebhook(array $payload, array $headers = []): PaymentProcessResult
    {
        // Los pagos en efectivo no generan webhooks externos.
        return PaymentProcessResult::failed(
            reference: $payload['reference'] ?? 'CASH-UNKNOWN',
            amount:    0.0,
            message:   'El gateway de efectivo no admite webhooks externos.',
            status:    PaymentStatus::Failed,
        );
    }
}
