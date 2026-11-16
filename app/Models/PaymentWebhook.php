<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentWebhook extends Model
{
    use HasFactory;

    protected $fillable = [
        'gateway',
        'external_event_id',
        'event_type',
        'payload',
        'headers',
        'signature',
        'status',
        'processed_at',
        'error',
    ];

    protected function casts(): array
    {
        return [
            'payload'      => 'array',
            'headers'      => 'array',
            'processed_at' => 'datetime',
        ];
    }
}
