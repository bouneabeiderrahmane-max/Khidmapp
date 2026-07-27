<?php

namespace App\DataTransferObjects\Pricing;

use Carbon\CarbonImmutable;

/**
 * Détail complet d'un calcul de prix (CDC 8.3). Toutes les valeurs sont
 * destinées à être historisées telles quelles sur une commande (8.3.2)
 * une fois le module Commande construit (Sprint 6) — ne rien recalculer
 * a posteriori à partir des paramètres courants.
 */
readonly class PriceBreakdown
{
    public function __construct(
        public float $basePriceEur,
        public string $currencyPair,
        public float $exchangeRate,
        public float $convertedMru,
        public float $marginPercent,
        public string $marginSource,
        public float $marginAmountMru,
        public string $deliveryZone,
        public float $deliveryFeeMru,
        public float $finalPriceMru,
        public CarbonImmutable $computedAt,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'base_price_eur' => $this->basePriceEur,
            'currency_pair' => $this->currencyPair,
            'exchange_rate' => $this->exchangeRate,
            'converted_mru' => $this->convertedMru,
            'margin_percent' => $this->marginPercent,
            'margin_source' => $this->marginSource,
            'margin_amount_mru' => $this->marginAmountMru,
            'delivery_zone' => $this->deliveryZone,
            'delivery_fee_mru' => $this->deliveryFeeMru,
            'final_price_mru' => $this->finalPriceMru,
            'computed_at' => $this->computedAt->toIso8601String(),
        ];
    }
}
