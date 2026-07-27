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
     * Ordre de précédence, du plus spécifique au moins spécifique.
     */
    public static function precedence(): array
    {
        return [self::CATEGORY, self::BOUTIQUE, self::GLOBAL];
    }
}
