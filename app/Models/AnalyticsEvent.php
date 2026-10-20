<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalyticsEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_type',
        'product_id',
        'user_id',
        'session_id',
        'device_type',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('event_type', $type);
    }

    public function scopeViews(Builder $query): Builder
    {
        return $query->where('event_type', 'product_view');
    }

    public function scopeCartAdds(Builder $query): Builder
    {
        return $query->where('event_type', 'add_to_cart');
    }

    public function scopeCheckouts(Builder $query): Builder
    {
        return $query->where('event_type', 'begin_checkout');
    }

    public function scopePurchases(Builder $query): Builder
    {
        return $query->where('event_type', 'purchase_completed');
    }
}
