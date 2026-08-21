<?php

namespace App\Support;

/**
 * Précédence confirmée (docs/PLAN.md §8, tranchée après clarification) :
 * une marge de catégorie prévaut sur une marge de boutique, qui prévaut sur
 * le défaut global — la règle la plus spécifique au produit gagne.
 */
final class MarginScope
{
    public const GLOBAL = 'global';

    public const BOUTIQUE = 'boutique';

    public const CATEGORY = 'category';

    /**
     * Source de marge quand ni catégorie, ni boutique, ni règle globale
     * explicite ne couvre le produit : paliers automatiques par prix EUR
     * (voir PriceMarginTier), invisibles au client au même titre que les
     * autres sources.
     */
    public const PRICE_TIER = 'price_tier';

    /**
     * Ordre de précédence, du plus spécifique au moins spécifique.
     */
    public static function precedence(): array
    {
        return [self::CATEGORY, self::BOUTIQUE, self::GLOBAL, self::PRICE_TIER];
    }
}
