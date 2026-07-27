<?php

namespace Tests\Feature\Catalog;

use App\Models\Boutique;
use App\Models\Category;
use App\Models\DeliveryFeeTier;
use App\Models\ExchangeRate;
use App\Models\MarginRule;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicProductSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        ExchangeRate::query()->create(['currency_pair' => 'EUR_MRU', 'rate' => 47.5, 'effective_at' => now()->subDay()]);
        MarginRule::query()->create(['scope_type' => 'global', 'scope_id' => null, 'percent' => 20, 'effective_at' => now()->subDay()]);
        DeliveryFeeTier::query()->create(['zone' => 'nouakchott', 'min_price_mru' => 0, 'max_price_mru' => null, 'fee_mru' => 350]);
    }

    private function productWithVariant(array $productAttrs = [], array $variantAttrs = []): Product
    {
        $product = Product::factory()->create($productAttrs);
        $product->variants()->create(array_merge([
            'external_variant_ref' => 'v-'.$product->id,
            'price_eur' => $product->base_price_eur,
            'stock_status' => 'in_stock',
        ], $variantAttrs));

        return $product;
    }

    public function test_only_active_products_from_active_boutiques_are_listed(): void
    {
        $activeBoutique = Boutique::factory()->active()->create();
        $inactiveBoutique = Boutique::factory()->create(['status' => 'inactive']);

        $this->productWithVariant(['boutique_id' => $activeBoutique->id, 'status' => 'active']);
        $this->productWithVariant(['boutique_id' => $activeBoutique->id, 'status' => 'indisponible']);
        $this->productWithVariant(['boutique_id' => $inactiveBoutique->id, 'status' => 'active']);

        $response = $this->getJson('/api/v1/products');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_keyword_search_matches_the_french_name(): void
    {
        $boutique = Boutique::factory()->active()->create();
        $this->productWithVariant(['boutique_id' => $boutique->id, 'name' => ['fr' => 'Robe rouge', 'ar' => 'فستان أحمر']]);
        $this->productWithVariant(['boutique_id' => $boutique->id, 'name' => ['fr' => 'Pantalon bleu', 'ar' => 'بنطال أزرق']]);

        $response = $this->getJson('/api/v1/products?q=robe');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertSame('Robe rouge', $response->json('data.0.name.fr'));
    }

    public function test_it_filters_by_boutique_slug(): void
    {
        $boutiqueA = Boutique::factory()->active()->create(['name' => 'Boutique A']);
        $boutiqueB = Boutique::factory()->active()->create(['name' => 'Boutique B']);
        $this->productWithVariant(['boutique_id' => $boutiqueA->id]);
        $this->productWithVariant(['boutique_id' => $boutiqueB->id]);

        $response = $this->getJson('/api/v1/products?boutique='.$boutiqueA->slug);

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertSame($boutiqueA->id, $response->json('data.0.boutique.id'));
    }

    public function test_it_filters_by_category_slug(): void
    {
        $boutique = Boutique::factory()->active()->create();
        $category = Category::factory()->create();
        $this->productWithVariant(['boutique_id' => $boutique->id, 'category_id' => $category->id]);
        $this->productWithVariant(['boutique_id' => $boutique->id, 'category_id' => null]);

        $response = $this->getJson('/api/v1/products?category='.$category->slug);

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_it_filters_by_color_and_size(): void
    {
        $boutique = Boutique::factory()->active()->create();
        $this->productWithVariant(['boutique_id' => $boutique->id], ['size' => 'M', 'color' => 'rouge']);
        $this->productWithVariant(['boutique_id' => $boutique->id], ['size' => 'L', 'color' => 'bleu']);

        $response = $this->getJson('/api/v1/products?size=M&color=rouge');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_it_filters_by_price_range_in_mru(): void
    {
        $boutique = Boutique::factory()->active()->create();
        $this->productWithVariant(['boutique_id' => $boutique->id, 'base_price_eur' => 10], ['price_eur' => 10]);
        $this->productWithVariant(['boutique_id' => $boutique->id, 'base_price_eur' => 100], ['price_eur' => 100]);

        // 10 EUR -> (10*47.5=475 +20%=95) +350 = 920 ; 100 EUR -> (4750+950)+350=6050
        $response = $this->getJson('/api/v1/products?min_price=5000&max_price=7000');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_it_sorts_by_price(): void
    {
        $boutique = Boutique::factory()->active()->create();
        $this->productWithVariant(['boutique_id' => $boutique->id, 'base_price_eur' => 50], ['price_eur' => 50]);
        $this->productWithVariant(['boutique_id' => $boutique->id, 'base_price_eur' => 10], ['price_eur' => 10]);

        $asc = $this->getJson('/api/v1/products?sort=price_asc')->json('data');
        $this->assertTrue($asc[0]['price_from_mru'] < $asc[1]['price_from_mru']);

        $desc = $this->getJson('/api/v1/products?sort=price_desc')->json('data');
        $this->assertTrue($desc[0]['price_from_mru'] > $desc[1]['price_from_mru']);
    }

    public function test_the_response_never_exposes_eur_price_or_source_details(): void
    {
        $boutique = Boutique::factory()->active()->create();
        $this->productWithVariant(['boutique_id' => $boutique->id]);

        $response = $this->getJson('/api/v1/products');
        $body = $response->getContent();

        $this->assertStringNotContainsString('base_price_eur', $body);
        $this->assertStringNotContainsString('source_url', $body);
        $this->assertStringNotContainsString('external_ref', $body);
    }

    public function test_results_are_paginated(): void
    {
        $boutique = Boutique::factory()->active()->create();
        for ($i = 0; $i < 3; $i++) {
            $this->productWithVariant(['boutique_id' => $boutique->id]);
        }

        $response = $this->getJson('/api/v1/products?per_page=2');

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
        $this->assertSame(3, $response->json('meta.total'));
    }
}
