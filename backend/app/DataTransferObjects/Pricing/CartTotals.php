<?php

namespace App\DataTransferObjects\Pricing;

/**
 * Simulation du prix final d'un panier avant validation (CDC 7.2.2),
 * consolidant tous les articles pour un unique calcul de frais de
 * livraison (voir ItemPriceBreakdown). Utilisée à la fois par l'aperçu du
 * panier et par le passage de commande, pour garantir que le total simulé
 * est bien celui effectivement facturé.
 */
readonly class CartTotals
{
    /**
     * @param  array<int, array{item: mixed, breakdown: ItemPriceBreakdown, line_subtotal_mru: float}>  $lines
     */
    public function __construct(
        public array $lines,
        public float $subtotalEur,
        public string $currencyPair,
        public float $exchangeRate,
        public float $subtotalMru,
        public string $deliveryZone,
        public float $deliveryFeeMru,
        public float $managementFeeMru,
        public float $totalMru,
    ) {}
}
