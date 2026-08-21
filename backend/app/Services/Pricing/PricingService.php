<?php

namespace App\Services\Pricing;

use App\DataTransferObjects\Pricing\ItemPriceBreakdown;
use App\DataTransferObjects\Pricing\PriceBreakdown;
use App\Exceptions\Pricing\MissingDeliveryFeeTierException;
use App\Exceptions\Pricing\MissingExchangeRateException;
use App\Models\DeliveryFeeTier;
use App\Models\ExchangeRate;
use App\Models\MarginRule;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\MarginScope;
use App\Support\PackageWeightTier;
use App\Support\PriceMarginTier;
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

    /**
     * Prix "à l'unité" d'une variante prise isolément (fiche produit,
     * aperçu admin) : conversion + marge + frais de livraison calculés sur
     * cette seule ligne. Pour une commande à plusieurs lignes, utiliser
     * subtotalForVariant() par ligne puis deliveryFeeForAmount() une seule
     * fois sur la somme (voir docblock d'ItemPriceBreakdown).
     */
    public function priceForVariant(ProductVariant $variant, ?string $zone = null): PriceBreakdown
    {
        $zone ??= config('pricing.default_zone');
        $item = $this->subtotalForVariant($variant);
        $deliveryFeeMru = $this->deliveryFeeForAmount($zone, $item->subtotalMru);
        $finalPriceMru = round($item->subtotalMru + $deliveryFeeMru, 2);

        return new PriceBreakdown(
            basePriceEur: $item->basePriceEur,
            currencyPair: $item->currencyPair,
            exchangeRate: $item->exchangeRate,
            convertedMru: $item->convertedMru,
            marginPercent: $item->marginPercent,
            marginSource: $item->marginSource,
            marginAmountMru: $item->marginAmountMru,
            deliveryZone: $zone,
            deliveryFeeMru: $deliveryFeeMru,
            finalPriceMru: $finalPriceMru,
            computedAt: CarbonImmutable::now(),
        );
    }

    /**
     * Conversion + marge pour une variante, sans frais de livraison.
     */
    public function subtotalForVariant(ProductVariant $variant): ItemPriceBreakdown
    {
        $variant->loadMissing('product');

        $basePriceEur = (float) $variant->price_eur;
        $currencyPair = config('pricing.default_currency_pair');

        $exchangeRate = $this->currentExchangeRate($currencyPair);
        $convertedMru = round($basePriceEur * $exchangeRate, 2);

        [$marginPercent, $marginSource] = $this->resolveMarginPercent($variant->product, $basePriceEur);

        $marginAmountMru = round($convertedMru * $marginPercent / 100, 2);
        $subtotalMru = round($convertedMru + $marginAmountMru, 2);

        return new ItemPriceBreakdown(
            basePriceEur: $basePriceEur,
            currencyPair: $currencyPair,
            exchangeRate: $exchangeRate,
            convertedMru: $convertedMru,
            marginPercent: $marginPercent,
            marginSource: $marginSource,
            marginAmountMru: $marginAmountMru,
            subtotalMru: $subtotalMru,
        );
    }

    /**
     * Conversion + marge pour un article hors catalogue ("pedido
     * personalizado", CDC — extension) : même moteur que subtotalForVariant
     * (précédence catégorie > boutique > global), mais à partir d'un prix
     * saisi par le client plutôt que d'une ProductVariant — il n'existe
     * donc pas de catégorie associée, seule la marge de boutique (si connue)
     * ou globale s'applique.
     */
    public function priceForExternalItem(float $priceEur, ?int $boutiqueId = null): ItemPriceBreakdown
    {
        $currencyPair = config('pricing.default_currency_pair');
        $exchangeRate = $this->currentExchangeRate($currencyPair);
        $convertedMru = round($priceEur * $exchangeRate, 2);

        [$marginPercent, $marginSource] = $this->resolveMarginPercentForScope(null, $boutiqueId, $priceEur);

        $marginAmountMru = round($convertedMru * $marginPercent / 100, 2);
        $subtotalMru = round($convertedMru + $marginAmountMru, 2);

        return new ItemPriceBreakdown(
            basePriceEur: $priceEur,
            currencyPair: $currencyPair,
            exchangeRate: $exchangeRate,
            convertedMru: $convertedMru,
            marginPercent: $marginPercent,
            marginSource: $marginSource,
            marginAmountMru: $marginAmountMru,
            subtotalMru: $subtotalMru,
        );
    }

    public function deliveryFeeForAmount(string $zone, float $amount): float
    {
        $tier = DeliveryFeeTier::query()->forAmount($zone, $amount)->orderByDesc('min_price_mru')->first();

        if ($tier === null) {
            throw MissingDeliveryFeeTierException::forZone($zone, $amount);
        }

        return (float) $tier->fee_mru;
    }

    /**
     * Frais de livraison au moment du paiement (panier réel, commande
     * manuelle, demande personnalisée) : si le client a choisi un poids de
     * colis (`PackageWeightTier`) pour la zone Nouakchott — seule zone pour
     * laquelle des tarifs par poids ont été fournis — ce choix prévaut sur
     * la grille par tranche de prix. Sans choix de poids (aperçu avant
     * l'étape de paiement) ou pour toute autre zone, on retombe sur
     * deliveryFeeForAmount() comme avant.
     */
    public function deliveryFee(string $zone, float $subtotalMru, ?string $weightTier = null, float $extraWeightKg = 0.0): float
    {
        if ($zone === 'nouakchott' && $weightTier !== null) {
            return PackageWeightTier::feeMru($weightTier, $extraWeightKg);
        }

        return $this->deliveryFeeForAmount($zone, $subtotalMru);
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
     * @return array{0: float, 1: string}
     */
    private function resolveMarginPercent(Product $product, float $priceEur): array
    {
        return $this->resolveMarginPercentForScope($product->category_id, $product->boutique_id, $priceEur);
    }

    /**
     * Précédence confirmée : catégorie > boutique > règle globale explicite
     * > paliers automatiques par prix (docs/PLAN.md §8, révisée). Une règle
     * "global" en base reste un levier admin explicite (ex. campagne
     * ponctuelle) qui prévaut sur les paliers ; en son absence — le cas
     * courant depuis le retrait du seed automatique — PriceMarginTier fixe
     * la marge selon la tranche de prix EUR, invisible au client.
     *
     * @return array{0: float, 1: string}
     */
    private function resolveMarginPercentForScope(?int $categoryId, ?int $boutiqueId, float $priceEur): array
    {
        $scopes = [
            [MarginScope::CATEGORY, $categoryId],
            [MarginScope::BOUTIQUE, $boutiqueId],
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

        return [PriceMarginTier::percentFor($priceEur), MarginScope::PRICE_TIER];
    }
}
