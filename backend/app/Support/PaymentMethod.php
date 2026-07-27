<?php

namespace App\Support;

/**
 * Modes de paiement confirmés par le CDC (8.5) : Bankily (automatique) ou
 * virement manuel avec preuve. L'intégration effective est le Sprint 7 ;
 * la commande enregistre seulement le mode choisi à la validation (7.2.2).
 */
final class PaymentMethod
{
    public const BANKILY = 'bankily';

    public const MANUAL = 'manual';

    public static function all(): array
    {
        return [self::BANKILY, self::MANUAL];
    }
}
