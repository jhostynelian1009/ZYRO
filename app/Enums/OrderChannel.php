<?php

namespace App\Enums;

enum OrderChannel: string
{
    case Web = 'web';
    case WhatsApp = 'whatsapp';
    case PuntoFisico = 'punto_fisico';

    public function label(): string
    {
        return match ($this) {
            self::Web => 'Tienda Web',
            self::WhatsApp => 'WhatsApp Business',
            self::PuntoFisico => 'Punto Físico (Guayaquil)',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Web => 'bg-blue-50 text-blue-700 border-blue-200',
            self::WhatsApp => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            self::PuntoFisico => 'bg-amber-50 text-amber-700 border-amber-200',
        };
    }
}
