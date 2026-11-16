<?php

namespace App\Enums;

enum ReturnRequestedSolution: string
{
    case CambioTalla = 'cambio_talla';
    case CambioColor = 'cambio_color';
    case DevolucionDinero = 'devolucion_dinero';
    case GarantiaDefecto = 'garantia_defecto';

    public function label(): string
    {
        return match ($this) {
            self::CambioTalla => 'Cambio por otra talla',
            self::CambioColor => 'Cambio por otro color o modelo',
            self::DevolucionDinero => 'Devolución de dinero (reembolso)',
            self::GarantiaDefecto => 'Aplicación de garantía por defecto',
        };
    }
}
