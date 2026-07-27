<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['label', 'city', 'area', 'geo_lat', 'geo_lng', 'phone', 'is_default'])]
class Address extends Model
{
    protected function casts(): array
    {
        return [
            'geo_lat' => 'decimal:7',
            'geo_lng' => 'decimal:7',
            'is_default' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
