<?php

namespace App\Enums;

enum ReturnRequestStatus: string
{
    case Recibida = 'recibida';
    case EnRevision = 'en_revision';
    case Resuelta = 'resuelta';
    case Rechazada = 'rechazada';

    public function label(): string
    {
        return match ($this) {
            self::Recibida => 'Recibida',
            self::EnRevision => 'En revisión',
            self::Resuelta => 'Resuelta',
            self::Rechazada => 'Rechazada',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Recibida => 'bg-amber-50 text-amber-700 border-amber-200',
            self::EnRevision => 'bg-indigo-50 text-indigo-700 border-indigo-200',
            self::Resuelta => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            self::Rechazada => 'bg-rose-50 text-rose-700 border-rose-200',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Resuelta, self::Rechazada], true);
    }
}
