<?php

namespace App\Payments\Gateways;

use App\Contracts\PaymentGatewayInterface;
use App\DTOs\PaymentProcessResult;
use App\Enums\PaymentStatus;
use App\Models\Order;
use Illuminate\Support\Str;

/**
 * BankTransferGateway — registra la transferencia bancaria.
 * El estado queda en "pending" hasta verificación manual del administrador.
 */
class BankTransferGateway implements PaymentGatewayInterface
{
    public function getIdentifier(): string
    {
        return 'bank_transfer';
    }

    public function getName(): string
    {
        return 'Transferencia Bancaria';
    }

    public function getMode(): string
    {
        return config('payment.mode', 'mock');
    }

    public function charge(Order $order, array $payload = []): PaymentProcessResult
    {
        $idempotencyKey = $payload['idempotency_key'] ?? Str::uuid()->toString();
        $reference      = 'TRF-' . date('Ymd') . '-' . strtoupper(Str::random(6));
        $amount         = (float) $order->total;

        // La transferencia bancaria requiere verificación manual: se devuelve Pending.
        return new PaymentProcessResult(
            success:               false,
            status:                PaymentStatus::Pending,
            reference:             $reference,
            amount:                $amount,
            externalTransactionId: $reference,
            currency:              'USD',
            message:               'Transferencia registrada. Pendiente de verificación por el administrador.',
            rawResponse: [
                'gateway'        => 'bank_transfer',
                'status'         => 'pending',
                'reference'      => $reference,
                'account_name'   => config('payment.bank_transfer.account_name', 'ZYRO S.A.'),
                'account_number' => config('payment.bank_transfer.account_number', '****'),
                'instructions'   => 'Envíe el comprobante al correo ventas@zyro.com',
            ],
            requiresRedirect: false,
            redirectUrl:      null,
            idempotencyKey:   $idempotencyKey,
        );
    }

    public function queryTransaction(string $transactionReference): PaymentProcessResult
    {
        // Sin API externa; el estado lo gestiona el administrador.
        return new PaymentProcessResult(
            success:               false,
            status:                PaymentStatus::Pending,
            reference:             $transactionReference,
            amount:                0.0,
            externalTransactionId: $transactionReference,
            currency:              'USD',
            message:               'Transferencia pendiente de verificación manual.',
            rawResponse:           ['gateway' => 'bank_transfer', 'query' => true],
        );
    }

    public function handleWebhook(array $payload, array $headers = []): PaymentProcessResult
    {
        // Verificación manual via panel admin — no hay webhooks externos.
        return PaymentProcessResult::failed(
            reference: $payload['reference'] ?? 'TRF-UNKNOWN',
            amount:    0.0,
            message:   'Las transferencias bancarias no generan webhooks automáticos.',
            status:    PaymentStatus::Pending,
        );
    }
}
