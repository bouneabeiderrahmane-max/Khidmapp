<?php

namespace Tests\Feature\Admin;

use App\Models\Boutique;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Support\OrderStatus;
use App\Support\Roles;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $serviceClient;

    private User $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole(Roles::ADMINISTRATEUR);

        $this->serviceClient = User::factory()->create();
        $this->serviceClient->assignRole(Roles::SERVICE_CLIENT);

        $this->client = User::factory()->create();
        $this->client->assignRole(Roles::CLIENT);
    }

    private function variant(): ProductVariant
    {
        $product = Product::factory()->create(['boutique_id' => Boutique::factory()]);

        return $product->variants()->create(['external_variant_ref' => 'v-'.$product->id, 'price_eur' => 30]);
    }

    private function makeOrder(string $status, string $zone, float $totalMru, float $subtotalMru): Order
    {
        return Order::query()->create([
            'user_id' => $this->client->id, 'status' => $status, 'payment_method' => 'manual',
            'subtotal_eur' => 20, 'exchange_rate_snapshot' => 10, 'subtotal_mru' => $subtotalMru,
            'delivery_fee_snapshot_mru' => $totalMru - $subtotalMru, 'delivery_zone' => $zone, 'total_mru' => $totalMru,
        ]);
    }

    public function test_the_full_report_computes_ca_average_basket_and_margin(): void
    {
        $variant = $this->variant();
        $boutique = $variant->product->boutique;

        $order = $this->makeOrder(OrderStatus::DELIVERED, 'nouakchott', 1200, 1000);
        OrderItem::query()->create([
            'order_id' => $order->id, 'product_variant_id' => $variant->id, 'boutique_id' => $boutique->id,
            'product_name_snapshot' => ['fr' => 'T-shirt'], 'quantity' => 2,
            'unit_price_eur' => 20, 'unit_price_mru_snapshot' => 500, 'margin_percent_snapshot' => 20,
            'margin_amount_mru_snapshot' => 83.33, 'margin_source_snapshot' => 'global',
        ]);

        $cancelled = $this->makeOrder(OrderStatus::CANCELLED, 'nouakchott', 900, 700);
        OrderItem::query()->create([
            'order_id' => $cancelled->id, 'product_variant_id' => $variant->id, 'boutique_id' => $boutique->id,
            'product_name_snapshot' => ['fr' => 'T-shirt'], 'quantity' => 1,
            'unit_price_eur' => 20, 'unit_price_mru_snapshot' => 700, 'margin_percent_snapshot' => 20,
            'margin_amount_mru_snapshot' => 116.67, 'margin_source_snapshot' => 'global',
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/admin/dashboard/report')
            ->assertOk();

        // Commande annulée exclue : seule la commande livrée compte.
        $response->assertJsonPath('data.ca_mru', 1200)
            ->assertJsonPath('data.order_count', 1)
            ->assertJsonPath('data.average_basket_mru', 1200)
            ->assertJsonPath('data.margin_realized_mru', 166.66)
            ->assertJsonPath('data.orders_delivered', 1)
            ->assertJsonPath('data.orders_in_progress', 0)
            ->assertJsonPath('data.sales_by_boutique.0.boutique_id', $boutique->id)
            ->assertJsonPath('data.sales_by_zone.0.zone', 'nouakchott');
    }

    public function test_a_service_client_only_sees_the_limited_view_without_financial_fields(): void
    {
        $variant = $this->variant();
        $order = $this->makeOrder(OrderStatus::DELIVERED, 'nouakchott', 1200, 1000);
        OrderItem::query()->create([
            'order_id' => $order->id, 'product_variant_id' => $variant->id, 'boutique_id' => $variant->product->boutique_id,
            'product_name_snapshot' => ['fr' => 'T-shirt'], 'quantity' => 1,
            'unit_price_eur' => 20, 'unit_price_mru_snapshot' => 1000, 'margin_percent_snapshot' => 20,
            'margin_amount_mru_snapshot' => 166.67, 'margin_source_snapshot' => 'global',
        ]);

        $response = $this->actingAs($this->serviceClient, 'api')
            ->getJson('/api/v1/admin/dashboard/report')
            ->assertOk();

        $response->assertJsonMissingPath('data.ca_mru')
            ->assertJsonMissingPath('data.margin_realized_mru')
            ->assertJsonMissingPath('data.average_basket_mru')
            ->assertJsonPath('data.order_count', 1)
            ->assertJsonMissingPath('data.sales_by_boutique.0.revenue_mru');
    }

    public function test_a_client_cannot_view_the_dashboard(): void
    {
        $this->actingAs($this->client, 'api')
            ->getJson('/api/v1/admin/dashboard/report')
            ->assertStatus(403);
    }

    public function test_only_the_full_level_can_export_csv(): void
    {
        $this->actingAs($this->serviceClient, 'api')
            ->getJson('/api/v1/admin/dashboard/report.csv')
            ->assertStatus(403);

        $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/admin/dashboard/report.csv')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }
}
