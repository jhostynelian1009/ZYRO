<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'image_path',
        'category',
        'size_options',
        'stock',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'size_options' => 'array',
            'stock' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        $like = '%'.addcslashes($term, '%_\\').'%';

        return $query->where(function (Builder $nested) use ($like) {
            $nested->where('name', 'like', $like)
                ->orWhere('description', 'like', $like);
        });
    }

    public function imageUrl(): string
    {
        if (filled($this->image_path)) {
            return asset($this->image_path);
        }

        return asset('images/products/placeholder.svg');
    }

    public function formattedPrice(): string
    {
        return '$'.number_format((float) $this->price, 2, '.', ',');
    }

    public function availableSizes(): array
    {
        return $this->size_options ?? [];
    }

    public function isPurchasable(): bool
    {
        return $this->is_active && $this->stock > 0;
    }

    protected static function booted(): void
    {
        static::creating(function (Product $product): void {
            if (blank($product->slug)) {
                $product->slug = Str::slug($product->name);
            }
        });
    }
}
