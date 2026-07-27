<?php

namespace Tests\Feature\Admin;

use App\Models\Boutique;
use App\Models\DeliveryFeeTier;
use App\Models\ExchangeRate;
use App\Models\MarginRule;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Support\OrderStatus;
use App\Support\Roles;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOrderControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $serviceClient;

    private User $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        ExchangeRate::query()->create(['currency_pair' => 'EUR_MRU', 'rate' => 10, 'effective_at' => now()->subDay()]);
        MarginRule::query()->create(['scope_type' => 'global', 'scope_id' => null, 'percent' => 0, 'effective_at' => now()->subDay()]);
        DeliveryFeeTier::query()->create(['zone' => 'nouakchott', 'min_price_mru' => 0, 'max_price_mru' => null, 'fee_mru' => 200]);

        $this->admin = User::factory()->create();
        $this->admin->assignRole(Roles::ADMINISTRATEUR);

        $this->serviceClient = User::factory()->create();
        $this->serviceClient->assignRole(Roles::SERVICE_CLIENT);

        $this->client = User::factory()->create();
        $this->client->assignRole(Roles::CLIENT);
    }

    private function variant(float $priceEur = 50): ProductVariant
    {
        $product = Product::factory()->create(['boutique_id' => Boutique::factory(), 'base_price_eur' => $priceEur]);

        return $product->variants()->create(['external_variant_ref' => 'v-'.$product->id, 'price_eur' => $priceEur, 'stock_status' => 'in_stock']);
    }

    public function test_a_client_is_forbidden_from_every_admin_order_endpoint(): void
    {
        $this->actingAs($this->client, 'api')->getJson('/api/v1/admin/orders')->assertForbidden();
        $this->actingAs($this->client, 'api')->postJson('/api/v1/admin/orders', [])->assertForbidden();
    }

    public function test_service_client_can_create_a_manual_order_and_is_recorded_as_the_actor(): void
    {
        $variant = $this->variant(50);
        $addressId = $this->client->addresses()->create(['label' => 'Domicile', 'city' => 'Nouakchott'])->id;

        $response = $this->actingAs($this->serviceClient, 'api')
            ->postJson('/api/v1/admin/orders', [
                'user_id' => $this->client->id,
                'address_id' => $addressId,
                'payment_method' => 'manual',
                'items' => [['product_variant_id' => $variant->id, 'quantity' => 1]],
            ])
            ->assertCreated();

        $response
            ->assertJsonPath('data.is_manual_order', true)
            ->assertJsonPath('data.status_history.0.actor_type', 'service_client');

        $this->assertSame($this->serviceClient->id, Order::query()->first()->created_by_agent_id);
    }

    public function test_a_manual_order_rejects_an_address_not_belonging_to_the_named_client(): void
    {
        $variant = $this->variant(50);
        $otherAddressId = $this->serviceClient->addresses()->create(['label' => 'Bureau', 'city' => 'Nouakchott'])->id;

        $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/admin/orders', [
                'user_id' => $this->client->id,
                'address_id' => $otherAddressId,
                'payment_method' => 'manual',
                'items' => [['product_variant_id' => $variant->id, 'quantity' => 1]],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['address_id']);
    }

    public function test_admin_can_advance_an_order_status_and_the_administrateur_actor_is_recorded(): void
    {
        $order = Order::query()->create([
            'user_id' => $this->client->id, 'status' => OrderStatus::AWAITING_PAYMENT, 'payment_method' => 'manual',
            'subtotal_eur' => 10, 'exchange_rate_snapshot' => 10, 'subtotal_mru' => 100,
            'delivery_fee_snapshot_mru' => 200, 'total_mru' => 300,
        ]);

        $this->actingAs($this->admin, 'api')
            ->patchJson("/api/v1/admin/orders/{$order->id}/status", ['status' => OrderStatus::PAYMENT_VALIDATED])
            ->assertOk()
            ->assertJsonPath('data.status', OrderStatus::PAYMENT_VALIDATED)
            ->assertJsonPath('data.status_history.0.actor_type', 'administrateur');
    }

    public function test_an_illegal_status_transition_returns_a_422(): void
    {
        $order = Order::query()->create([
            'user_id' => $this->client->id, 'status' => OrderStatus::AWAITING_PAYMENT, 'payment_method' => 'manual',
            'subtotal_eur' => 10, 'exchange_rate_snapshot' => 10, 'subtotal_mru' => 100,
            'delivery_fee_snapshot_mru' => 200, 'total_mru' => 300,
        ]);

        $this->actingAs($this->admin, 'api')
            ->patchJson("/api/v1/admin/orders/{$order->id}/status", ['status' => OrderStatus::DELIVERED])
            ->assertStatus(422);
    }

    public function test_refund_without_a_note_is_rejected_but_succeeds_with_one(): void
    {
        $order = Order::query()->create([
            'user_id' => $this->client->id, 'status' => OrderStatus::DELIVERED, 'payment_method' => 'manual',
            'subtotal_eur' => 10, 'exchange_rate_snapshot' => 10, 'subtotal_mru' => 100,
            'delivery_fee_snapshot_mru' => 200, 'total_mru' => 300,
        ]);

        $this->actingAs($this->admin, 'api')
            ->patchJson("/api/v1/admin/orders/{$order->id}/status", ['status' => OrderStatus::REFUNDED])
            ->assertStatus(422);

        $this->actingAs($this->admin, 'api')
            ->patchJson("/api/v1/admin/orders/{$order->id}/status", ['status' => OrderStatus::REFUNDED, 'note' => 'Remboursement accordé.'])
            ->assertOk()
            ->assertJsonPath('data.status', OrderStatus::REFUNDED);
    }

    public function test_the_order_list_can_be_filtered_by_status_and_client(): void
    {
        Order::query()->create([
            'user_id' => $this->client->id, 'status' => OrderStatus::AWAITING_PAYMENT, 'payment_method' => 'manual',
            'subtotal_eur' => 10, 'exchange_rate_snapshot' => 10, 'subtotal_mru' => 100,
            'delivery_fee_snapshot_mru' => 200, 'total_mru' => 300,
        ]);
        Order::query()->create([
            'user_id' => $this->client->id, 'status' => OrderStatus::DELIVERED, 'payment_method' => 'manual',
            'subtotal_eur' => 10, 'exchange_rate_snapshot' => 10, 'subtotal_mru' => 100,
            'delivery_fee_snapshot_mru' => 200, 'total_mru' => 300,
        ]);

        $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/admin/orders?status='.OrderStatus::DELIVERED)
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }
}
