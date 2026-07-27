<?php

namespace Tests\Feature\Boutique;

use App\Models\Boutique;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Support\OrderStatus;
use App\Support\Roles;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminBoutiqueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_a_guest_cannot_access_the_admin_boutiques_endpoint(): void
    {
        $this->getJson('/api/v1/admin/boutiques')->assertStatus(401);
    }

    public function test_a_client_cannot_access_the_admin_boutiques_endpoint(): void
    {
        $client = User::factory()->create();
        $client->assignRole(Roles::CLIENT);

        $this->actingAs($client, 'api')
            ->getJson('/api/v1/admin/boutiques')
            ->assertStatus(403);
    }

    public function test_service_client_cannot_manage_boutiques(): void
    {
        $agent = User::factory()->create();
        $agent->assignRole(Roles::SERVICE_CLIENT);

        $this->actingAs($agent, 'api')
            ->postJson('/api/v1/admin/boutiques', ['name' => 'X', 'base_url' => 'https://x.test', 'country_code' => 'ES', 'currency_code' => 'EUR'])
            ->assertStatus(403);
    }

    public function test_an_administrateur_sees_boutiques_of_every_status(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Roles::ADMINISTRATEUR);

        Boutique::factory()->create(['status' => 'en_test']);
        Boutique::factory()->create(['status' => 'inactive']);
        Boutique::factory()->active()->create();

        $response = $this->actingAs($admin, 'api')->getJson('/api/v1/admin/boutiques');

        $response->assertOk();
        $this->assertCount(3, $response->json('data'));
    }

    public function test_creating_a_boutique_without_a_status_defaults_to_en_test(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Roles::ADMINISTRATEUR);

        $response = $this->actingAs($admin, 'api')->postJson('/api/v1/admin/boutiques', [
            'name' => 'Nouvelle boutique',
            'base_url' => 'https://example.com',
            'country_code' => 'ES',
            'currency_code' => 'EUR',
        ]);

        $response->assertCreated()->assertJsonPath('data.status', 'en_test');
        $this->assertDatabaseHas('boutiques', ['name' => 'Nouvelle boutique', 'status' => 'en_test']);
    }

    public function test_an_administrateur_can_update_a_boutique(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Roles::ADMINISTRATEUR);
        $boutique = Boutique::factory()->create(['default_margin_percent' => null]);

        $this->actingAs($admin, 'api')
            ->putJson("/api/v1/admin/boutiques/{$boutique->id}", ['default_margin_percent' => 15])
            ->assertOk()
            ->assertJsonPath('data.default_margin_percent', '15.00');
    }

    public function test_an_administrateur_can_transition_the_status(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Roles::ADMINISTRATEUR);
        $boutique = Boutique::factory()->create(['status' => 'en_test']);

        $this->actingAs($admin, 'api')
            ->patchJson("/api/v1/admin/boutiques/{$boutique->id}/status", ['status' => 'active'])
            ->assertOk()
            ->assertJsonPath('data.status', 'active');

        $this->assertDatabaseHas('boutiques', ['id' => $boutique->id, 'status' => 'active']);
    }

    public function test_an_invalid_status_is_rejected(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Roles::ADMINISTRATEUR);
        $boutique = Boutique::factory()->create();

        $this->actingAs($admin, 'api')
            ->patchJson("/api/v1/admin/boutiques/{$boutique->id}/status", ['status' => 'closed_forever'])
            ->assertStatus(422);
    }

    public function test_an_administrateur_can_delete_a_deletable_boutique(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Roles::ADMINISTRATEUR);
        $boutique = Boutique::factory()->create();

        $this->actingAs($admin, 'api')
            ->deleteJson("/api/v1/admin/boutiques/{$boutique->id}")
            ->assertOk();

        $this->assertSoftDeleted($boutique);
    }

    /**
     * Une boutique référencée par une commande non terminale (annule,
     * rembourse, livre) ne peut pas être supprimée : Boutique::canBeDeleted().
     */
    public function test_a_boutique_with_a_non_terminal_order_cannot_be_deleted(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Roles::ADMINISTRATEUR);

        $boutique = Boutique::factory()->create();
        $product = Product::factory()->create(['boutique_id' => $boutique->id]);
        $variant = $product->variants()->create(['external_variant_ref' => 'v-1', 'price_eur' => 10, 'stock_status' => 'in_stock']);
        $client = User::factory()->create();

        $order = Order::query()->create([
            'user_id' => $client->id, 'status' => OrderStatus::AWAITING_PAYMENT, 'payment_method' => 'manual',
            'subtotal_eur' => 10, 'exchange_rate_snapshot' => 10, 'subtotal_mru' => 100,
            'delivery_fee_snapshot_mru' => 200, 'total_mru' => 300,
        ]);
        $order->items()->create([
            'product_variant_id' => $variant->id, 'boutique_id' => $boutique->id,
            'product_name_snapshot' => $product->name, 'quantity' => 1,
            'unit_price_eur' => 10, 'unit_price_mru_snapshot' => 100,
            'margin_percent_snapshot' => 0, 'margin_source_snapshot' => 'global',
        ]);

        $this->actingAs($admin, 'api')
            ->deleteJson("/api/v1/admin/boutiques/{$boutique->id}")
            ->assertStatus(422);

        $this->assertDatabaseHas('boutiques', ['id' => $boutique->id, 'deleted_at' => null]);
    }

    public function test_a_boutique_becomes_deletable_once_its_orders_reach_a_terminal_status(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Roles::ADMINISTRATEUR);

        $boutique = Boutique::factory()->create();
        $product = Product::factory()->create(['boutique_id' => $boutique->id]);
        $variant = $product->variants()->create(['external_variant_ref' => 'v-1', 'price_eur' => 10, 'stock_status' => 'in_stock']);
        $client = User::factory()->create();

        $order = Order::query()->create([
            'user_id' => $client->id, 'status' => OrderStatus::CANCELLED, 'payment_method' => 'manual',
            'subtotal_eur' => 10, 'exchange_rate_snapshot' => 10, 'subtotal_mru' => 100,
            'delivery_fee_snapshot_mru' => 200, 'total_mru' => 300,
        ]);
        $order->items()->create([
            'product_variant_id' => $variant->id, 'boutique_id' => $boutique->id,
            'product_name_snapshot' => $product->name, 'quantity' => 1,
            'unit_price_eur' => 10, 'unit_price_mru_snapshot' => 100,
            'margin_percent_snapshot' => 0, 'margin_source_snapshot' => 'global',
        ]);

        $this->actingAs($admin, 'api')
            ->deleteJson("/api/v1/admin/boutiques/{$boutique->id}")
            ->assertOk();

        $this->assertSoftDeleted($boutique);
    }
}
