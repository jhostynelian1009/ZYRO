<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case Expired = 'expired';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Pending   => 'Pendiente',
            self::Approved  => 'Aprobado',
            self::Rejected  => 'Rechazado',
            self::Failed    => 'Fallido',
            self::Cancelled => 'Cancelado',
            self::Expired   => 'Expirado',
            self::Refunded  => 'Reembolsado',
        };
    }

    public function isApproved(): bool
    {
        return $this === self::Approved;
    }

    public function isFinal(): bool
    {
        return in_array($this, [
            self::Approved,
            self::Rejected,
            self::Failed,
            self::Cancelled,
            self::Expired,
            self::Refunded,
        ], true);
    }
}
