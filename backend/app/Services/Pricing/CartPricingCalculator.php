<?php

namespace App\Services\Pricing;

use App\DataTransferObjects\Pricing\CartTotals;
use Illuminate\Support\Collection;

class CartPricingCalculator
{
    public function __construct(private readonly PricingService $pricing) {}

    /**
     * @param  Collection<int, mixed>  $items  CartItem ou OrderItem-like : doit exposer ->variant et ->quantity
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

        $deliveryFeeMru = $items->isNotEmpty() ? $this->pricing->deliveryFeeForAmount($zone, $subtotalMru) : 0.0;
        $totalMru = round($subtotalMru + $deliveryFeeMru, 2);

        return new CartTotals(
            lines: $lines,
            subtotalEur: round($subtotalEur, 2),
            currencyPair: $currencyPair,
            exchangeRate: $exchangeRate,
            subtotalMru: round($subtotalMru, 2),
            deliveryZone: $zone,
            deliveryFeeMru: $deliveryFeeMru,
            totalMru: $totalMru,
        );
    }
}
