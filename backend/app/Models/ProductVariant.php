<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['external_variant_ref', 'sku', 'size', 'color', 'price_eur', 'stock_status'])]
class ProductVariant extends Model
{
    protected function casts(): array
    {
        return [
            'price_eur' => 'decimal:2',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
