<?php

namespace App\Enums;

enum PaymentGatewayDriver: string
{
    case Mock = 'mock';
    case PayPhone = 'payphone';
    case Deuna = 'deuna';
    case BankTransfer = 'bank_transfer';
    case Cash = 'cash';

    public function label(): string
    {
        return match ($this) {
            self::Mock         => 'Pasarela Simulada (MOCK)',
            self::PayPhone     => 'PayPhone Ecuador',
            self::Deuna        => 'Deuna (Banco Pichincha)',
            self::BankTransfer => 'Transferencia Bancaria',
            self::Cash         => 'Efectivo / Cobro Presencial',
        };
    }
}
