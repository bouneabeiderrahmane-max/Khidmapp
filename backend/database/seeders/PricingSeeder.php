<?php

namespace Database\Seeders;

use App\Models\DeliveryFeeTier;
use App\Models\ExchangeRate;
use App\Models\MarginRule;
use App\Support\MarginScope;
use Illuminate\Database\Seeder;

class PricingSeeder extends Seeder
{
    /**
     * Valeurs de départ raisonnables, reprises de l'exemple chiffré du
     * cahier des charges (8.3.1) : 1 € = 47,50 MRU, marge par défaut 20 %,
     * frais de livraison forfaitaires 350 MRU vers Nouakchott. Modifiables
     * à tout moment depuis l'administration.
     */
    public function run(): void
    {
        if (ExchangeRate::query()->where('currency_pair', 'EUR_MRU')->doesntExist()) {
            ExchangeRate::query()->create([
                'currency_pair' => 'EUR_MRU',
                'rate' => 47.50,
                'effective_at' => now(),
            ]);
        }

        if (MarginRule::query()->where('scope_type', MarginScope::GLOBAL)->doesntExist()) {
            MarginRule::query()->create([
                'scope_type' => MarginScope::GLOBAL,
                'scope_id' => null,
                'percent' => config('pricing.default_margin_percent'),
                'effective_at' => now(),
            ]);
        }

        if (DeliveryFeeTier::query()->where('zone', 'nouakchott')->doesntExist()) {
            DeliveryFeeTier::query()->create([
                'zone' => 'nouakchott',
                'min_price_mru' => 0,
                'max_price_mru' => null,
                'fee_mru' => 350,
            ]);
        }
    }
}
