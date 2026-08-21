<?php

namespace Tests\Unit\Services;

use App\Exceptions\Pricing\MissingDeliveryFeeTierException;
use App\Exceptions\Pricing\MissingExchangeRateException;
use App\Models\Boutique;
use App\Models\Category;
use App\Models\DeliveryFeeTier;
use App\Models\ExchangeRate;
use App\Models\MarginRule;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Pricing\PricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PricingServiceTest extends TestCase
{
    use RefreshDatabase;

    private PricingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PricingService::class);
    }

    private function variantWithPrice(float $priceEur, ?int $boutiqueId = null, ?int $categoryId = null): ProductVariant
    {
        $product = Product::factory()->create([
            'boutique_id' => $boutiqueId ?? Boutique::factory()->create()->id,
            'category_id' => $categoryId,
            'base_price_eur' => $priceEur,
        ]);

        return $product->variants()->create([
            'external_variant_ref' => 'v-'.$product->id,
            'price_eur' => $priceEur,
            'stock_status' => 'in_stock',
        ]);
    }

    /**
     * Cas de référence du CDC (8.3.1) : 29,95 € à 47,50 MRU/€, marge 20 %,
     * livraison forfaitaire 350 MRU => 2 057,16 MRU.
     */
    public function test_the_cdc_reference_case_matches_exactly(): void
    {
        ExchangeRate::query()->create(['currency_pair' => 'EUR_MRU', 'rate' => 47.50, 'effective_at' => now()->subDay()]);
        MarginRule::query()->create(['scope_type' => 'global', 'scope_id' => null, 'percent' => 20, 'effective_at' => now()->subDay()]);
        DeliveryFeeTier::query()->create(['zone' => 'nouakchott', 'min_price_mru' => 0, 'max_price_mru' => null, 'fee_mru' => 350]);

        $variant = $this->variantWithPrice(29.95);
        $breakdown = $this->service->priceForVariant($variant);

        $this->assertSame(1422.63, $breakdown->convertedMru);
        $this->assertSame(284.53, $breakdown->marginAmountMru);
        $this->assertSame(350.0, $breakdown->deliveryFeeMru);
        $this->assertSame(2057.16, $breakdown->finalPriceMru);
    }

    public function test_a_category_margin_prevails_over_a_boutique_margin(): void
    {
        ExchangeRate::query()->create(['currency_pair' => 'EUR_MRU', 'rate' => 10, 'effective_at' => now()->subDay()]);
        DeliveryFeeTier::query()->create(['zone' => 'nouakchott', 'min_price_mru' => 0, 'max_price_mru' => null, 'fee_mru' => 0]);
        MarginRule::query()->create(['scope_type' => 'global', 'scope_id' => null, 'percent' => 20, 'effective_at' => now()->subDay()]);

        $boutique = Boutique::factory()->create();
        $category = Category::factory()->create();
        MarginRule::query()->create(['scope_type' => 'boutique', 'scope_id' => $boutique->id, 'percent' => 30, 'effective_at' => now()->subDay()]);
        MarginRule::query()->create(['scope_type' => 'category', 'scope_id' => $category->id, 'percent' => 10, 'effective_at' => now()->subDay()]);

        $variant = $this->variantWithPrice(100, $boutique->id, $category->id);
        $breakdown = $this->service->priceForVariant($variant);

        $this->assertSame('category', $breakdown->marginSource);
        $this->assertSame(10.0, $breakdown->marginPercent);
    }

    /**
     * Sans aucune règle en base (catégorie, boutique ou global explicite),
     * la marge par défaut suit désormais les paliers de prix EUR
     * (PriceMarginTier) plutôt qu'une valeur plate — voir
     * PricingSeeder (qui ne crée plus de règle "global" automatiquement)
     * et la migration remove_auto_seeded_global_margin_rules.
     */
    public function test_the_price_tier_is_used_when_no_margin_rule_covers_the_product(): void
    {
        ExchangeRate::query()->create(['currency_pair' => 'EUR_MRU', 'rate' => 10, 'effective_at' => now()->subDay()]);
        DeliveryFeeTier::query()->create(['zone' => 'nouakchott', 'min_price_mru' => 0, 'max_price_mru' => null, 'fee_mru' => 0]);

        $cheap = $this->service->priceForVariant($this->variantWithPrice(150));
        $mid = $this->service->priceForVariant($this->variantWithPrice(500));
        $expensive = $this->service->priceForVariant($this->variantWithPrice(900));

        $this->assertSame('price_tier', $cheap->marginSource);
        $this->assertSame(20.0, $cheap->marginPercent);

        $this->assertSame('price_tier', $mid->marginSource);
        $this->assertSame(15.0, $mid->marginPercent);

        $this->assertSame('price_tier', $expensive->marginSource);
        $this->assertSame(10.0, $expensive->marginPercent);
    }

    /**
     * Une règle "global" explicite (créée par un administrateur, ex.
     * campagne ponctuelle) reste prioritaire sur les paliers automatiques.
     */
    public function test_an_explicit_global_margin_rule_still_prevails_over_the_price_tier(): void
    {
        ExchangeRate::query()->create(['currency_pair' => 'EUR_MRU', 'rate' => 10, 'effective_at' => now()->subDay()]);
        DeliveryFeeTier::query()->create(['zone' => 'nouakchott', 'min_price_mru' => 0, 'max_price_mru' => null, 'fee_mru' => 0]);
        MarginRule::query()->create(['scope_type' => 'global', 'scope_id' => null, 'percent' => 5, 'effective_at' => now()->subDay()]);

        $breakdown = $this->service->priceForVariant($this->variantWithPrice(150));

        $this->assertSame('global', $breakdown->marginSource);
        $this->assertSame(5.0, $breakdown->marginPercent);
    }

    public function test_a_boutique_margin_prevails_over_the_global_default_when_no_category_is_set(): void
    {
        ExchangeRate::query()->create(['currency_pair' => 'EUR_MRU', 'rate' => 10, 'effective_at' => now()->subDay()]);
        DeliveryFeeTier::query()->create(['zone' => 'nouakchott', 'min_price_mru' => 0, 'max_price_mru' => null, 'fee_mru' => 0]);
        MarginRule::query()->create(['scope_type' => 'global', 'scope_id' => null, 'percent' => 20, 'effective_at' => now()->subDay()]);

        $boutique = Boutique::factory()->create();
        MarginRule::query()->create(['scope_type' => 'boutique', 'scope_id' => $boutique->id, 'percent' => 30, 'effective_at' => now()->subDay()]);

        $variant = $this->variantWithPrice(100, $boutique->id);
        $breakdown = $this->service->priceForVariant($variant);

        $this->assertSame('boutique', $breakdown->marginSource);
        $this->assertSame(30.0, $breakdown->marginPercent);
    }

    public function test_the_most_recent_exchange_rate_is_used(): void
    {
        ExchangeRate::query()->create(['currency_pair' => 'EUR_MRU', 'rate' => 40, 'effective_at' => now()->subDays(2)]);
        ExchangeRate::query()->create(['currency_pair' => 'EUR_MRU', 'rate' => 50, 'effective_at' => now()->subDay()]);
        MarginRule::query()->create(['scope_type' => 'global', 'scope_id' => null, 'percent' => 0, 'effective_at' => now()->subDay()]);
        DeliveryFeeTier::query()->create(['zone' => 'nouakchott', 'min_price_mru' => 0, 'max_price_mru' => null, 'fee_mru' => 0]);

        $variant = $this->variantWithPrice(10);
        $breakdown = $this->service->priceForVariant($variant);

        $this->assertSame(50.0, $breakdown->exchangeRate);
    }

    public function test_a_future_exchange_rate_is_ignored(): void
    {
        ExchangeRate::query()->create(['currency_pair' => 'EUR_MRU', 'rate' => 40, 'effective_at' => now()->subDay()]);
        ExchangeRate::query()->create(['currency_pair' => 'EUR_MRU', 'rate' => 999, 'effective_at' => now()->addDay()]);
        MarginRule::query()->create(['scope_type' => 'global', 'scope_id' => null, 'percent' => 0, 'effective_at' => now()->subDay()]);
        DeliveryFeeTier::query()->create(['zone' => 'nouakchott', 'min_price_mru' => 0, 'max_price_mru' => null, 'fee_mru' => 0]);

        $variant = $this->variantWithPrice(10);
        $breakdown = $this->service->priceForVariant($variant);

        $this->assertSame(40.0, $breakdown->exchangeRate);
    }

    public function test_the_delivery_tier_matching_the_subtotal_is_selected(): void
    {
        ExchangeRate::query()->create(['currency_pair' => 'EUR_MRU', 'rate' => 1, 'effective_at' => now()->subDay()]);
        MarginRule::query()->create(['scope_type' => 'global', 'scope_id' => null, 'percent' => 0, 'effective_at' => now()->subDay()]);
        DeliveryFeeTier::query()->create(['zone' => 'nouakchott', 'min_price_mru' => 0, 'max_price_mru' => 999, 'fee_mru' => 100]);
        DeliveryFeeTier::query()->create(['zone' => 'nouakchott', 'min_price_mru' => 1000, 'max_price_mru' => null, 'fee_mru' => 200]);

        $cheap = $this->service->priceForVariant($this->variantWithPrice(50));
        $expensive = $this->service->priceForVariant($this->variantWithPrice(1500));

        $this->assertSame(100.0, $cheap->deliveryFeeMru);
        $this->assertSame(200.0, $expensive->deliveryFeeMru);
    }

    public function test_it_throws_when_no_exchange_rate_is_configured(): void
    {
        MarginRule::query()->create(['scope_type' => 'global', 'scope_id' => null, 'percent' => 20, 'effective_at' => now()->subDay()]);
        DeliveryFeeTier::query()->create(['zone' => 'nouakchott', 'min_price_mru' => 0, 'max_price_mru' => null, 'fee_mru' => 350]);

        $this->expectException(MissingExchangeRateException::class);
        $this->service->priceForVariant($this->variantWithPrice(10));
    }

    public function test_it_throws_when_no_delivery_tier_covers_the_amount(): void
    {
        ExchangeRate::query()->create(['currency_pair' => 'EUR_MRU', 'rate' => 47.5, 'effective_at' => now()->subDay()]);
        MarginRule::query()->create(['scope_type' => 'global', 'scope_id' => null, 'percent' => 20, 'effective_at' => now()->subDay()]);

        $this->expectException(MissingDeliveryFeeTierException::class);
        $this->service->priceForVariant($this->variantWithPrice(10));
    }
}
