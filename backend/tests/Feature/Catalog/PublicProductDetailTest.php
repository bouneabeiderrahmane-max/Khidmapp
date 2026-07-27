<?php

namespace Tests\Feature\Catalog;

use App\Models\Boutique;
use App\Models\DeliveryFeeTier;
use App\Models\ExchangeRate;
use App\Models\MarginRule;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicProductDetailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        ExchangeRate::query()->create(['currency_pair' => 'EUR_MRU', 'rate' => 47.5, 'effective_at' => now()->subDay()]);
        MarginRule::query()->create(['scope_type' => 'global', 'scope_id' => null, 'percent' => 20, 'effective_at' => now()->subDay()]);
        DeliveryFeeTier::query()->create(['zone' => 'nouakchott', 'min_price_mru' => 0, 'max_price_mru' => null, 'fee_mru' => 350]);
    }

    public function test_it_returns_the_full_detail_with_mru_prices_per_variant(): void
    {
        $boutique = Boutique::factory()->active()->create();
        $product = Product::factory()->create(['boutique_id' => $boutique->id, 'base_price_eur' => 29.95]);
        $product->variants()->create(['external_variant_ref' => 'v1', 'size' => 'S', 'price_eur' => 29.95, 'stock_status' => 'in_stock']);
        $product->variants()->create(['external_variant_ref' => 'v2', 'size' => 'M', 'price_eur' => 29.95, 'stock_status' => 'in_stock']);

        $response = $this->getJson("/api/v1/products/{$product->id}");

        $response->assertOk()
            ->assertJsonPath('data.variants.0.price_mru', 2057.16)
            ->assertJsonPath('data.variants.1.price_mru', 2057.16)
            ->assertJsonCount(2, 'data.variants');

        $this->assertArrayHasKey('delivery_estimate_days', $response->json('data'));
    }

    public function test_it_returns_404_for_an_unavailable_product(): void
    {
        $boutique = Boutique::factory()->active()->create();
        $product = Product::factory()->create(['boutique_id' => $boutique->id, 'status' => 'indisponible']);

        $this->getJson("/api/v1/products/{$product->id}")->assertStatus(404);
    }

    public function test_it_returns_404_when_the_boutique_is_not_active(): void
    {
        $boutique = Boutique::factory()->create(['status' => 'en_test']);
        $product = Product::factory()->create(['boutique_id' => $boutique->id, 'status' => 'active']);

        $this->getJson("/api/v1/products/{$product->id}")->assertStatus(404);
    }
}
