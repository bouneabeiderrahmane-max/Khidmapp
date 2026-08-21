<?php

namespace Database\Seeders;

use App\Models\DeliveryFeeTier;
use App\Models\ExchangeRate;
use Illuminate\Database\Seeder;

class PricingSeeder extends Seeder
{
    /**
     * Valeurs de départ raisonnables, reprises de l'exemple chiffré du
     * cahier des charges (8.3.1) : 1 € = 47,50 MRU, frais de livraison
     * forfaitaires 350 MRU vers Nouakchott. Modifiables à tout moment
     * depuis l'administration.
     *
     * Ne crée volontairement plus de règle de marge "global" : la marge par
     * défaut est désormais calculée par paliers de prix (PriceMarginTier),
     * pas par une valeur plate en base — voir PricingService. Un
     * administrateur reste libre d'ajouter une règle "global" explicite
     * (campagne ponctuelle) via l'API, qui prévaudra alors sur les paliers.
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
