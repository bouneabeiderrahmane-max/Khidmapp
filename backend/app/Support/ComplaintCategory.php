<?php

namespace App\Support;

/**
 * Catégories de réclamation confirmées par le cahier des charges (8.8).
 */
final class ComplaintCategory
{
    public const PRODUIT_NON_CONFORME = 'produit_non_conforme';

    public const RETARD = 'retard';

    public const DOMMAGE = 'dommage';

    public const ERREUR_FACTURATION = 'erreur_facturation';

    public const AUTRE = 'autre';

    public static function all(): array
    {
        return [
            self::PRODUIT_NON_CONFORME,
            self::RETARD,
            self::DOMMAGE,
            self::ERREUR_FACTURATION,
            self::AUTRE,
        ];
    }
}
