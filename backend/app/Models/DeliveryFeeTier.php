<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class DeliveryFeeTier extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'min_price_mru' => 'decimal:2',
            'max_price_mru' => 'decimal:2',
            'fee_mru' => 'decimal:2',
        ];
    }

    /**
     * Palier applicable pour un montant donné dans une zone donnée
     * (8.9.4 : grille par tranche de prix, différenciée par zone).
     */
    public function scopeForAmount(Builder $query, string $zone, float $amount): Builder
    {
        return $query->where('zone', $zone)
            ->where('min_price_mru', '<=', $amount)
            ->where(function (Builder $q) use ($amount) {
                $q->whereNull('max_price_mru')->orWhere('max_price_mru', '>=', $amount);
            });
    }
}
