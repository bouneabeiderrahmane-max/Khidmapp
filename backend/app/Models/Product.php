<?php

namespace App\Models;

use App\Support\ProductStatus;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'boutique_id', 'external_ref', 'category_id', 'name', 'description', 'translation_locked',
    'images', 'base_price_eur', 'status', 'source_url', 'last_synced_at', 'unavailable_since',
])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'name' => 'array',
            'description' => 'array',
            'images' => 'array',
            'base_price_eur' => 'decimal:2',
            'last_synced_at' => 'datetime',
            'unavailable_since' => 'datetime',
            'translation_locked' => 'boolean',
        ];
    }

    public function boutique(): BelongsTo
    {
        return $this->belongsTo(Boutique::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', ProductStatus::ACTIVE);
    }
}
