<?php

namespace App\Support;

/**
 * Statuts produit confirmés par le cahier des charges (8.2.2) : un produit
 * non retrouvé lors d'une synchronisation est marqué "indisponible", puis
 * "discontinued" (retiré du catalogue actif) après le délai configurable.
 */
final class ProductStatus
{
    public const ACTIVE = 'active';

    public const INDISPONIBLE = 'indisponible';

    public const DISCONTINUED = 'discontinued';

    public static function all(): array
    {
        return [self::ACTIVE, self::INDISPONIBLE, self::DISCONTINUED];
    }
}
