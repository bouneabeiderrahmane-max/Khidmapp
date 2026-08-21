<?php

namespace App\Services\Pricing;

use App\DataTransferObjects\Pricing\CartTotals;
use App\Models\CustomOrderItem;
use Illuminate\Support\Collection;

/**
 * Équivalent de CartPricingCalculator pour un "pedido personalizado" : les
 * lignes sont des CustomOrderItem (prix estimé par le client + boutique
 * optionnelle) plutôt que des CartItem adossés à une ProductVariant
 * connue. Même schéma de calcul (sous-total par ligne, frais de livraison
 * une seule fois sur la somme) pour rester cohérent avec le flux panier
 * normal — voir CustomOrderRequestService::approve().
 */
class CustomOrderPricingCalculator
{
    public function __construct(private readonly PricingService $pricing) {}

    /**
     * @param  Collection<int, CustomOrderItem>  $items
     */
    public function calculate(Collection $items, ?string $zone = null): CartTotals
    {
        $zone ??= config('pricing.default_zone');

        $lines = [];
        $subtotalEur = 0.0;
        $subtotalMru = 0.0;
        $exchangeRate = 0.0;
        $currencyPair = config('pricing.default_currency_pair');

        foreach ($items as $item) {
            $breakdown = $this->pricing->priceForExternalItem((float) $item->estimated_price_eur, $item->boutique_id);
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

        $deliveryFeeMru = $items->isNotEmpty() ? $this->pricing->deliveryFeeForAmount($zone, $subtotalMru) : 0.0;
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
