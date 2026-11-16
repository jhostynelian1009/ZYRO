<?php

namespace App\Models;

use App\Enums\ReturnInspectedCondition;
use App\Enums\ReturnRequestedSolution;
use App\Enums\ReturnRequestStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ReturnRequest extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'request_number',
        'order_id',
        'user_id',
        'order_item_id',
        'product_id',
        'quantity',
        'size',
        'reason',
        'problem_description',
        'requested_solution',
        'status',
        'reviewer_id',
        'inspected_condition',
        'replacement_product_id',
        'replacement_size',
        'admin_decision',
        'is_restocked',
        'restocked_at',
        'resolved_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'status' => ReturnRequestStatus::class,
            'inspected_condition' => ReturnInspectedCondition::class,
            'requested_solution' => ReturnRequestedSolution::class,
            'is_restocked' => 'boolean',
            'restocked_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function replacementProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'replacement_product_id');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(ReturnRequestHistory::class);
    }

    public static function generateRequestNumber(): string
    {
        do {
            $number = 'DEV-'.strtoupper(Str::random(8));
        } while (static::query()->where('request_number', $number)->exists());

        return $number;
    }
}
