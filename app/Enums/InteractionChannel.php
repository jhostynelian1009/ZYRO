<?php

namespace App\Enums;

enum InteractionChannel: string
{
    case WhatsApp = 'whatsapp';
    case PuntoFisico = 'punto_fisico';
    case Llamada = 'llamada';
    case Email = 'email';
    case Web = 'web';

    public function label(): string
    {
        return match ($this) {
            self::WhatsApp => 'WhatsApp Business',
            self::PuntoFisico => 'Punto Físico (Guayaquil)',
            self::Llamada => 'Llamada telefónica',
            self::Email => 'Correo electrónico',
            self::Web => 'Formulario Web',
        };
    }
}
