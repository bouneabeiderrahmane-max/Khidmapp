<?php

namespace Tests\Feature\Pricing;

use App\Models\DeliveryFeeTier;
use App\Models\ExchangeRate;
use App\Models\MarginRule;
use App\Models\Product;
use App\Models\User;
use App\Support\Roles;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductPricePreviewTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->assignRole(Roles::ADMINISTRATEUR);

        ExchangeRate::query()->create(['currency_pair' => 'EUR_MRU', 'rate' => 47.5, 'effective_at' => now()->subDay()]);
        MarginRule::query()->create(['scope_type' => 'global', 'scope_id' => null, 'percent' => 20, 'effective_at' => now()->subDay()]);
        DeliveryFeeTier::query()->create(['zone' => 'nouakchott', 'min_price_mru' => 0, 'max_price_mru' => null, 'fee_mru' => 350]);
    }

    public function test_it_returns_the_cdc_reference_breakdown(): void
    {
        $product = Product::factory()->create(['base_price_eur' => 29.95]);
        $variant = $product->variants()->create([
            'external_variant_ref' => 'v1', 'price_eur' => 29.95, 'stock_status' => 'in_stock',
        ]);

        $this->actingAs($this->admin, 'api')
            ->getJson("/api/v1/admin/products/{$product->id}/price-preview?variant_id={$variant->id}")
            ->assertOk()
            ->assertJsonPath('data.final_price_mru', 2057.16);
    }

    public function test_it_requires_a_variant_id(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->admin, 'api')
            ->getJson("/api/v1/admin/products/{$product->id}/price-preview")
            ->assertStatus(422);
    }

    public function test_a_client_cannot_preview_prices(): void
    {
        $product = Product::factory()->create();
        $variant = $product->variants()->create([
            'external_variant_ref' => 'v1', 'price_eur' => 10, 'stock_status' => 'in_stock',
        ]);
        $client = User::factory()->create();
        $client->assignRole(Roles::CLIENT);

        $this->actingAs($client, 'api')
            ->getJson("/api/v1/admin/products/{$product->id}/price-preview?variant_id={$variant->id}")
            ->assertStatus(403);
    }
}
