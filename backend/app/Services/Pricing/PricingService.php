<?php

namespace App\Services\Pricing;

use App\DataTransferObjects\Pricing\PriceBreakdown;
use App\Exceptions\Pricing\MissingDeliveryFeeTierException;
use App\Exceptions\Pricing\MissingExchangeRateException;
use App\Models\DeliveryFeeTier;
use App\Models\ExchangeRate;
use App\Models\MarginRule;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\MarginScope;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;

/**
 * Moteur de calcul de prix (CDC 8.3) : prix boutique (EUR) → conversion
 * MRU → majoration commerciale → frais de livraison → prix final.
 *
 * Chaque étape est arrondie à 2 décimales, conformément à l'exemple
 * chiffré du CDC (8.3.1) : 29,95 € à 47,50 MRU/€ avec 20 % de marge et
 * 350 MRU de livraison donne 2 057,16 MRU — ce cas sert de test de
 * référence (voir PricingServiceTest).
 */
class PricingService
{
    private const EXCHANGE_RATE_CACHE_TTL_SECONDS = 300;

    public function priceForVariant(ProductVariant $variant, ?string $zone = null): PriceBreakdown
    {
        $variant->loadMissing('product');
        $zone ??= config('pricing.default_zone');

        $basePriceEur = (float) $variant->price_eur;
        $currencyPair = config('pricing.default_currency_pair');

        $exchangeRate = $this->currentExchangeRate($currencyPair);
        $convertedMru = round($basePriceEur * $exchangeRate, 2);

        [$marginPercent, $marginSource] = $this->resolveMarginPercent($variant->product);

        $marginAmountMru = round($convertedMru * $marginPercent / 100, 2);
        $subtotalMru = round($convertedMru + $marginAmountMru, 2);

        $deliveryFeeMru = $this->resolveDeliveryFee($zone, $subtotalMru);
        $finalPriceMru = round($subtotalMru + $deliveryFeeMru, 2);

        return new PriceBreakdown(
            basePriceEur: $basePriceEur,
            currencyPair: $currencyPair,
            exchangeRate: $exchangeRate,
            convertedMru: $convertedMru,
            marginPercent: $marginPercent,
            marginSource: $marginSource,
            marginAmountMru: $marginAmountMru,
            deliveryZone: $zone,
            deliveryFeeMru: $deliveryFeeMru,
            finalPriceMru: $finalPriceMru,
            computedAt: CarbonImmutable::now(),
        );
    }

    /**
     * Le taux de change change rarement (mise à jour manuelle par un
     * administrateur) mais est lu à chaque calcul de prix — mis en cache
     * (CDC 10.1 : "cache pour les données à forte fréquence de lecture,
     * catalogue, taux de change"). Invalidé par
     * self::forgetExchangeRateCache() dès qu'un nouveau taux est enregistré.
     */
    private function currentExchangeRate(string $currencyPair): float
    {
        $rate = Cache::remember(
            self::exchangeRateCacheKey($currencyPair),
            self::EXCHANGE_RATE_CACHE_TTL_SECONDS,
            fn () => ExchangeRate::query()->effectiveAsOf($currencyPair)->first()?->rate,
        );

        if ($rate === null) {
            throw MissingExchangeRateException::forPair($currencyPair);
        }

        return (float) $rate;
    }

    public static function forgetExchangeRateCache(string $currencyPair): void
    {
        Cache::forget(self::exchangeRateCacheKey($currencyPair));
    }

    private static function exchangeRateCacheKey(string $currencyPair): string
    {
        return "pricing:exchange_rate:{$currencyPair}";
    }

    /**
     * Précédence confirmée : catégorie > boutique > global (docs/PLAN.md §8).
     *
     * @return array{0: float, 1: string}
     */
    private function resolveMarginPercent(Product $product): array
    {
        $scopes = [
            [MarginScope::CATEGORY, $product->category_id],
            [MarginScope::BOUTIQUE, $product->boutique_id],
            [MarginScope::GLOBAL, null],
        ];

        foreach ($scopes as [$scopeType, $scopeId]) {
            if ($scopeType !== MarginScope::GLOBAL && $scopeId === null) {
                continue;
            }

            $rule = MarginRule::query()->effectiveAsOf($scopeType, $scopeId)->first();

            if ($rule !== null) {
                return [(float) $rule->percent, $scopeType];
            }
        }

        // Aucune règle "global" en base (ne devrait pas arriver en pratique,
        // PricingSeeder en crée une) : repli sur la config applicative.
        return [(float) config('pricing.default_margin_percent'), MarginScope::GLOBAL];
    }

    private function resolveDeliveryFee(string $zone, float $amount): float
    {
        $tier = DeliveryFeeTier::query()->forAmount($zone, $amount)->orderByDesc('min_price_mru')->first();

        if ($tier === null) {
            throw MissingDeliveryFeeTierException::forZone($zone, $amount);
        }

        return (float) $tier->fee_mru;
    }
}
