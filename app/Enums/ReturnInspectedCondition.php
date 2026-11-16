<?php

namespace App\Enums;

enum ReturnInspectedCondition: string
{
    case Pendiente = 'pendiente';
    case OptimoConEtiquetas = 'optimo_con_etiquetas';
    case UsadoLimpio = 'usado_limpio';
    case DefectoFabrica = 'defecto_fabrica';
    case NoApto = 'no_apto';

    public function label(): string
    {
        return match ($this) {
            self::Pendiente => 'Pendiente de inspección física',
            self::OptimoConEtiquetas => 'Óptimo (nuevo con etiquetas)',
            self::UsadoLimpio => 'Usado limpio (sin defectos)',
            self::DefectoFabrica => 'Defecto de confección / fábrica comprobado',
            self::NoApto => 'No apto (daño o alteración por uso)',
        };
    }

    /**
     * Determina si la prenda devuelta puede reincorporarse al stock vendible.
     */
    public function isRestockable(): bool
    {
        return $this === self::OptimoConEtiquetas;
    }
}
