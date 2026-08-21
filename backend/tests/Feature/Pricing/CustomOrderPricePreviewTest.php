<?php

namespace Tests\Feature\Pricing;

use App\Models\Boutique;
use App\Models\DeliveryFeeTier;
use App\Models\ExchangeRate;
use App\Models\MarginRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomOrderPricePreviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        ExchangeRate::query()->create(['currency_pair' => 'EUR_MRU', 'rate' => 47.5, 'effective_at' => now()->subDay()]);
        MarginRule::query()->create(['scope_type' => 'global', 'scope_id' => null, 'percent' => 20, 'effective_at' => now()->subDay()]);
        DeliveryFeeTier::query()->create(['zone' => 'nouakchott', 'min_price_mru' => 0, 'max_price_mru' => null, 'fee_mru' => 350]);
    }

    public function test_it_is_publicly_accessible_and_applies_the_global_margin(): void
    {
        // 29.95 EUR * 47.5 = 1422.63 MRU, +20% = 1707.16 MRU (arrondis à chaque étape).
        $this->getJson('/api/v1/custom-order-price-preview?price_eur=29.95')
            ->assertOk()
            ->assertJsonPath('data.margin_source', 'global')
            ->assertJsonPath('data.subtotal_mru', 1707.16);
    }

    public function test_it_applies_the_boutique_margin_when_a_boutique_is_given(): void
    {
        $boutique = Boutique::factory()->create();
        MarginRule::query()->create(['scope_type' => 'boutique', 'scope_id' => $boutique->id, 'percent' => 50, 'effective_at' => now()->subDay()]);

        $this->getJson("/api/v1/custom-order-price-preview?price_eur=10&boutique_id={$boutique->id}")
            ->assertOk()
            ->assertJsonPath('data.margin_source', 'boutique')
            ->assertJsonPath('data.subtotal_mru', 712.5);
    }

    public function test_it_falls_back_to_the_price_tier_when_no_margin_rule_covers_the_boutique(): void
    {
        MarginRule::query()->delete();

        // 900 EUR * 47.5 = 42750 MRU, palier >700€ => 10 %, +10% = 47025 MRU.
        $this->getJson('/api/v1/custom-order-price-preview?price_eur=900')
            ->assertOk()
            ->assertJsonPath('data.margin_source', 'price_tier')
            ->assertJsonPath('data.margin_percent', 10)
            ->assertJsonPath('data.subtotal_mru', 47025);
    }

    public function test_it_requires_a_positive_price(): void
    {
        $this->getJson('/api/v1/custom-order-price-preview?price_eur=0')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['price_eur']);
    }
}
