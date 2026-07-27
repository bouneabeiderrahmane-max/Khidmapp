<?php

namespace App\DataTransferObjects\Pricing;

/**
 * Sous-total d'une ligne (conversion + marge), sans frais de livraison.
 * Les frais de livraison sont calculés une seule fois pour l'ensemble
 * d'une commande, sur la somme des sous-totaux de ses lignes — pas ligne
 * par ligne — puisqu'un envoi est consolidé (CDC 8.6 : "Consolidation des
 * colis d'un même client ou d'un même envoi groupé").
 */
readonly class ItemPriceBreakdown
{
    public function __construct(
        public float $basePriceEur,
        public string $currencyPair,
        public float $exchangeRate,
        public float $convertedMru,
        public float $marginPercent,
        public string $marginSource,
        public float $marginAmountMru,
        public float $subtotalMru,
    ) {}
}
