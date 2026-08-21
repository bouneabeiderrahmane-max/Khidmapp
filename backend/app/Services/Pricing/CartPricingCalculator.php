<?php

namespace App\Services\Pricing;

use App\DataTransferObjects\Pricing\CartTotals;
use Illuminate\Support\Collection;

class CartPricingCalculator
{
    public function __construct(private readonly PricingService $pricing) {}

    /**
     * @param  Collection<int, mixed>  $items  CartItem ou OrderItem-like : doit exposer ->variant et ->quantity
     * @param  string|null  $weightTier  Poids de colis choisi par le client au paiement (PackageWeightTier) —
     *                                   null pour un aperçu avant cette étape (ex. GET /cart), auquel cas
     *                                   la grille par tranche de prix sert d'estimation (voir PricingService::deliveryFee()).
     */
    public function calculate(Collection $items, ?string $zone = null, ?string $weightTier = null, float $extraWeightKg = 0.0): CartTotals
    {
        $zone ??= config('pricing.default_zone');

        $lines = [];
        $subtotalEur = 0.0;
        $subtotalMru = 0.0;
        $exchangeRate = 0.0;
        $currencyPair = config('pricing.default_currency_pair');

        foreach ($items as $item) {
            $breakdown = $this->pricing->subtotalForVariant($item->variant);
            $exchangeRate = $breakdown->exchangeRate;
            $currencyPair = $breakdown->currencyPair;

            $lineSubtotalMru = round($breakdown->subtotalMru * $item->quantity, 2);
            $subtotalEur += $breakdown->basePriceEur * $item->quantity;
            $subtotalMru += $lineSubtotalMru;

            $lines[] = [
                'item' => $item,
                'breakdown' => $breakdown,
                'line_subtotal_mru' => $lineSubtotalMru,
            ];
        }

        $deliveryFeeMru = $items->isNotEmpty() ? $this->pricing->deliveryFee($zone, $subtotalMru, $weightTier, $extraWeightKg) : 0.0;
        $managementFeeMru = $items->isNotEmpty()
            ? round(($subtotalMru + $deliveryFeeMru) * config('pricing.management_fee_percent') / 100, 2)
            : 0.0;
        $totalMru = round($subtotalMru + $deliveryFeeMru + $managementFeeMru, 2);

        return new CartTotals(
            lines: $lines,
            subtotalEur: round($subtotalEur, 2),
            currencyPair: $currencyPair,
            exchangeRate: $exchangeRate,
            subtotalMru: round($subtotalMru, 2),
            deliveryZone: $zone,
            deliveryFeeMru: $deliveryFeeMru,
            managementFeeMru: $managementFeeMru,
            totalMru: $totalMru,
        );
    }
}
