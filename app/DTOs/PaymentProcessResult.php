<?php

namespace App\DTOs;

use App\Enums\PaymentStatus;

class PaymentProcessResult
{
    public function __construct(
        public readonly bool $success,
        public readonly PaymentStatus $status,
        public readonly string $reference,
        public readonly float $amount,
        public readonly ?string $externalTransactionId = null,
        public readonly string $currency = 'USD',
        public readonly string $message = '',
        public readonly array $rawResponse = [],
        public readonly bool $requiresRedirect = false,
        public readonly ?string $redirectUrl = null,
        public readonly ?string $idempotencyKey = null,
    ) {}

    public static function approved(
        string $reference,
        float $amount,
        ?string $externalTransactionId = null,
        string $message = 'Pago aprobado exitosamente.',
        array $rawResponse = [],
        ?string $idempotencyKey = null,
    ): self {
        return new self(
            success: true,
            status: PaymentStatus::Approved,
            reference: $reference,
            amount: $amount,
            externalTransactionId: $externalTransactionId,
            currency: 'USD',
            message: $message,
            rawResponse: $rawResponse,
            requiresRedirect: false,
            redirectUrl: null,
            idempotencyKey: $idempotencyKey,
        );
    }

    public static function failed(
        string $reference,
        float $amount,
        string $message,
        PaymentStatus $status = PaymentStatus::Failed,
        ?string $externalTransactionId = null,
        array $rawResponse = [],
        ?string $idempotencyKey = null,
    ): self {
        return new self(
            success: false,
            status: $status,
            reference: $reference,
            amount: $amount,
            externalTransactionId: $externalTransactionId,
            currency: 'USD',
            message: $message,
            rawResponse: $rawResponse,
            requiresRedirect: false,
            redirectUrl: null,
            idempotencyKey: $idempotencyKey,
        );
    }

    public static function redirect(
        string $reference,
        float $amount,
        string $redirectUrl,
        ?string $externalTransactionId = null,
        array $rawResponse = [],
        ?string $idempotencyKey = null,
    ): self {
        return new self(
            success: false,
            status: PaymentStatus::Pending,
            reference: $reference,
            amount: $amount,
            externalTransactionId: $externalTransactionId,
            currency: 'USD',
            message: 'Redirección requerida para autenticación de pago.',
            rawResponse: $rawResponse,
            requiresRedirect: true,
            redirectUrl: $redirectUrl,
            idempotencyKey: $idempotencyKey,
        );
    }

    public function toArray(): array
    {
        return [
            'success'                 => $this->success,
            'status'                  => $this->status->value,
            'reference'               => $this->reference,
            'amount'                  => $this->amount,
            'external_transaction_id' => $this->externalTransactionId,
            'currency'                => $this->currency,
            'message'                 => $this->message,
            'requires_redirect'       => $this->requiresRedirect,
            'redirect_url'            => $this->redirectUrl,
            'idempotency_key'         => $this->idempotencyKey,
        ];
    }
}
