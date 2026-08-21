<?php

namespace App\Support;

/**
 * Marge par défaut appliquée quand aucune règle explicite (catégorie ou
 * boutique) ne couvre un produit : paliers par prix EUR affiché au client,
 * décidés pour rester invisibles au client (seul le prix final MRU lui est
 * montré, jamais ce pourcentage ni le prix EUR) — inférieur à 200 € : 20 %,
 * de 200 à 700 € : 15 %, au-delà de 700 € : 10 %. Remplace l'ancien défaut
 * plat (config('pricing.default_margin_percent')) comme dernier maillon de
 * PricingService::resolveMarginPercentForScope().
 */
final class PriceMarginTier
{
    public static function percentFor(float $priceEur): float
    {
        if ($priceEur < 200) {
            return 20.0;
        }

        if ($priceEur <= 700) {
            return 15.0;
        }

        return 10.0;
    }
}
