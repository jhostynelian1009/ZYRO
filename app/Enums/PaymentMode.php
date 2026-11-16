<?php

namespace App\Enums;

enum PaymentMode: string
{
    case Mock = 'mock';
    case Sandbox = 'sandbox';
    case Live = 'live';

    public function label(): string
    {
        return match ($this) {
            self::Mock    => 'MOCK (Simulación de Pruebas)',
            self::Sandbox => 'SANDBOX (Ambiente Comercial de Pruebas)',
            self::Live    => 'LIVE (Producción Real)',
        };
    }

    public function isProductionAllowed(): bool
    {
        return $this === self::Live;
    }
}
