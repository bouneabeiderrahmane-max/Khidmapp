<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExchangeRate extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:6',
            'effective_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeEffectiveAsOf(Builder $query, string $currencyPair, ?DateTimeInterface $at = null): Builder
    {
        return $query->where('currency_pair', $currencyPair)
            ->where('effective_at', '<=', $at ?? now())
            ->orderByDesc('effective_at');
    }
}
