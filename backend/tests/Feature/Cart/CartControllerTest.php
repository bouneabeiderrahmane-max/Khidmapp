<?php

namespace Tests\Feature\Cart;

use App\Models\Boutique;
use App\Models\CartItem;
use App\Models\DeliveryFeeTier;
use App\Models\ExchangeRate;
use App\Models\MarginRule;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Support\BoutiqueStatus;
use App\Support\ProductStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    protected function setUp(): void
    {
        parent::setUp();

        ExchangeRate::query()->create(['currency_pair' => 'EUR_MRU', 'rate' => 10, 'effective_at' => now()->subDay()]);
        MarginRule::query()->create(['scope_type' => 'global', 'scope_id' => null, 'percent' => 0, 'effective_at' => now()->subDay()]);
        DeliveryFeeTier::query()->create(['zone' => 'nouakchott', 'min_price_mru' => 0, 'max_price_mru' => null, 'fee_mru' => 200]);

        $this->client = User::factory()->create();
    }

    private function activeVariant(float $priceEur = 50): ProductVariant
    {
        $boutique = Boutique::factory()->create(['status' => BoutiqueStatus::ACTIVE]);
        $product = Product::factory()->create(['boutique_id' => $boutique->id, 'status' => ProductStatus::ACTIVE, 'base_price_eur' => $priceEur]);

        return $product->variants()->create(['external_variant_ref' => 'v-'.$product->id, 'price_eur' => $priceEur, 'stock_status' => 'in_stock']);
    }

    public function test_the_cart_starts_empty(): void
    {
        $this->actingAs($this->client, 'api')
            ->getJson('/api/v1/cart')
            ->assertOk()
            ->assertJsonPath('data.items', [])
            ->assertJsonPath('data.total_mru', 0);
    }

    public function test_adding_an_item_creates_the_cart_and_returns_totals(): void
    {
        $variant = $this->activeVariant(50);

        $this->actingAs($this->client, 'api')
            ->postJson('/api/v1/cart/items', ['product_variant_id' => $variant->id, 'quantity' => 2])
            ->assertOk()
            ->assertJsonPath('data.subtotal_mru', 1000)
            ->assertJsonPath('data.delivery_fee_mru', 200)
            // Coût de gestion 5% sur (sous-total + livraison) : (1000+200)*5% = 60.
            ->assertJsonPath('data.management_fee_mru', 60)
            ->assertJsonPath('data.total_mru', 1260);
    }

    public function test_adding_the_same_variant_twice_increments_the_quantity_instead_of_duplicating(): void
    {
        $variant = $this->activeVariant(50);

        $this->actingAs($this->client, 'api')->postJson('/api/v1/cart/items', ['product_variant_id' => $variant->id, 'quantity' => 1]);
        $response = $this->actingAs($this->client, 'api')->postJson('/api/v1/cart/items', ['product_variant_id' => $variant->id, 'quantity' => 2]);

        $response->assertOk();
        $this->assertCount(1, $response->json('data.items'));
        $this->assertSame(3, $response->json('data.items.0.quantity'));
    }

    public function test_an_inactive_product_cannot_be_added_to_the_cart(): void
    {
        $boutique = Boutique::factory()->create(['status' => BoutiqueStatus::ACTIVE]);
        $product = Product::factory()->create(['boutique_id' => $boutique->id, 'status' => ProductStatus::INDISPONIBLE]);
        $variant = $product->variants()->create(['external_variant_ref' => 'v-'.$product->id, 'price_eur' => 10, 'stock_status' => 'in_stock']);

        $this->actingAs($this->client, 'api')
            ->postJson('/api/v1/cart/items', ['product_variant_id' => $variant->id])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['product_variant_id']);
    }

    public function test_a_variant_from_an_inactive_boutique_cannot_be_added_to_the_cart(): void
    {
        $boutique = Boutique::factory()->create(['status' => BoutiqueStatus::EN_PAUSE]);
        $product = Product::factory()->create(['boutique_id' => $boutique->id, 'status' => ProductStatus::ACTIVE]);
        $variant = $product->variants()->create(['external_variant_ref' => 'v-'.$product->id, 'price_eur' => 10, 'stock_status' => 'in_stock']);

        $this->actingAs($this->client, 'api')
            ->postJson('/api/v1/cart/items', ['product_variant_id' => $variant->id])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['product_variant_id']);
    }

    public function test_updating_the_quantity_recalculates_totals(): void
    {
        $variant = $this->activeVariant(50);
        $this->actingAs($this->client, 'api')->postJson('/api/v1/cart/items', ['product_variant_id' => $variant->id, 'quantity' => 1]);
        $cartItemId = CartItem::query()->first()->id;

        $this->actingAs($this->client, 'api')
            ->putJson("/api/v1/cart/items/{$cartItemId}", ['quantity' => 4])
            ->assertOk()
            ->assertJsonPath('data.items.0.quantity', 4)
            ->assertJsonPath('data.subtotal_mru', 2000);
    }

    public function test_removing_an_item_empties_the_cart(): void
    {
        $variant = $this->activeVariant(50);
        $this->actingAs($this->client, 'api')->postJson('/api/v1/cart/items', ['product_variant_id' => $variant->id]);
        $cartItemId = CartItem::query()->first()->id;

        $this->actingAs($this->client, 'api')
            ->deleteJson("/api/v1/cart/items/{$cartItemId}")
            ->assertOk()
            ->assertJsonPath('data.items', []);
    }

    public function test_a_client_cannot_modify_another_clients_cart_item(): void
    {
        $variant = $this->activeVariant(50);
        $this->actingAs($this->client, 'api')->postJson('/api/v1/cart/items', ['product_variant_id' => $variant->id]);
        $cartItemId = CartItem::query()->first()->id;

        $stranger = User::factory()->create();

        $this->actingAs($stranger, 'api')
            ->putJson("/api/v1/cart/items/{$cartItemId}", ['quantity' => 2])
            ->assertForbidden();

        $this->actingAs($stranger, 'api')
            ->deleteJson("/api/v1/cart/items/{$cartItemId}")
            ->assertForbidden();
    }

    public function test_the_cart_requires_authentication(): void
    {
        $this->getJson('/api/v1/cart')->assertUnauthorized();
    }
}
