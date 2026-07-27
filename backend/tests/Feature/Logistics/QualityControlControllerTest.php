<?php

namespace Tests\Feature\Logistics;

use App\Models\Boutique;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Support\OrderStatus;
use App\Support\Roles;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QualityControlControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $agent;

    private User $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->agent = User::factory()->create();
        $this->agent->assignRole(Roles::SERVICE_CLIENT);

        $this->client = User::factory()->create();
        $this->client->assignRole(Roles::CLIENT);
    }

    private function orderItem(string $orderStatus): OrderItem
    {
        $order = Order::query()->create([
            'user_id' => $this->client->id, 'status' => $orderStatus, 'payment_method' => 'manual',
            'subtotal_eur' => 10, 'exchange_rate_snapshot' => 10, 'subtotal_mru' => 100,
            'delivery_fee_snapshot_mru' => 200, 'total_mru' => 300,
        ]);

        $product = Product::factory()->create(['boutique_id' => Boutique::factory()]);

        return $order->items()->create([
            'product_variant_id' => null, 'boutique_id' => $product->boutique_id,
            'product_name_snapshot' => $product->name, 'quantity' => 1,
            'unit_price_eur' => 10, 'unit_price_mru_snapshot' => 100,
            'margin_percent_snapshot' => 0, 'margin_source_snapshot' => 'global',
        ]);
    }

    public function test_a_client_is_forbidden(): void
    {
        $item = $this->orderItem(OrderStatus::QUALITY_CONTROL);

        $this->actingAs($this->client, 'api')
            ->postJson("/api/v1/admin/orders/{$item->order_id}/items/{$item->id}/quality-control", ['is_conforme' => true])
            ->assertForbidden();
    }

    public function test_a_conforming_report_does_not_require_notes(): void
    {
        $item = $this->orderItem(OrderStatus::QUALITY_CONTROL);

        $this->actingAs($this->agent, 'api')
            ->postJson("/api/v1/admin/orders/{$item->order_id}/items/{$item->id}/quality-control", ['is_conforme' => true])
            ->assertCreated()
            ->assertJsonPath('data.is_conforme', true)
            ->assertJsonPath('data.reported_by', $this->agent->name);
    }

    public function test_a_non_conforming_report_requires_notes(): void
    {
        $item = $this->orderItem(OrderStatus::QUALITY_CONTROL);

        $this->actingAs($this->agent, 'api')
            ->postJson("/api/v1/admin/orders/{$item->order_id}/items/{$item->id}/quality-control", ['is_conforme' => false])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['notes']);

        $this->actingAs($this->agent, 'api')
            ->postJson("/api/v1/admin/orders/{$item->order_id}/items/{$item->id}/quality-control", [
                'is_conforme' => false,
                'notes' => 'Taille reçue (M) différente de la commande (S).',
            ])
            ->assertCreated()
            ->assertJsonPath('data.is_conforme', false);
    }

    public function test_a_report_is_rejected_outside_the_quality_control_status(): void
    {
        $item = $this->orderItem(OrderStatus::RECEIVED_MADRID);

        $this->actingAs($this->agent, 'api')
            ->postJson("/api/v1/admin/orders/{$item->order_id}/items/{$item->id}/quality-control", ['is_conforme' => true])
            ->assertStatus(422);
    }

    public function test_an_order_item_from_another_order_is_rejected(): void
    {
        $itemA = $this->orderItem(OrderStatus::QUALITY_CONTROL);
        $itemB = $this->orderItem(OrderStatus::QUALITY_CONTROL);

        $this->actingAs($this->agent, 'api')
            ->postJson("/api/v1/admin/orders/{$itemA->order_id}/items/{$itemB->id}/quality-control", ['is_conforme' => true])
            ->assertNotFound();
    }

    public function test_multiple_reports_can_be_listed_for_an_order(): void
    {
        $item = $this->orderItem(OrderStatus::QUALITY_CONTROL);

        $this->actingAs($this->agent, 'api')
            ->postJson("/api/v1/admin/orders/{$item->order_id}/items/{$item->id}/quality-control", ['is_conforme' => false, 'notes' => 'Défaut visuel.']);

        $this->actingAs($this->agent, 'api')
            ->getJson("/api/v1/admin/orders/{$item->order_id}/quality-control")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }
}
