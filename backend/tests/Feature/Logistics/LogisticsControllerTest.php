<?php

namespace Tests\Feature\Logistics;

use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\User;
use App\Support\OrderStatus;
use App\Support\Roles;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogisticsControllerTest extends TestCase
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

    private function makeOrder(string $status, \DateTimeInterface $enteredAt): Order
    {
        $order = Order::query()->create([
            'user_id' => $this->client->id, 'status' => $status, 'payment_method' => 'manual',
            'subtotal_eur' => 10, 'exchange_rate_snapshot' => 10, 'subtotal_mru' => 100,
            'delivery_fee_snapshot_mru' => 200, 'total_mru' => 300,
        ]);

        OrderStatusHistory::query()->create([
            'order_id' => $order->id, 'from_status' => null, 'to_status' => $status,
            'actor_type' => 'system', 'created_at' => $enteredAt, 'updated_at' => $enteredAt,
        ]);

        return $order;
    }

    public function test_a_client_is_forbidden_from_the_dashboard_and_alerts(): void
    {
        $this->actingAs($this->client, 'api')->getJson('/api/v1/admin/logistics/dashboard')->assertForbidden();
        $this->actingAs($this->client, 'api')->getJson('/api/v1/admin/logistics/alerts')->assertForbidden();
    }

    public function test_the_dashboard_counts_active_orders_per_status(): void
    {
        $this->makeOrder(OrderStatus::AWAITING_PAYMENT, now());
        $this->makeOrder(OrderStatus::AWAITING_PAYMENT, now());
        $this->makeOrder(OrderStatus::QUALITY_CONTROL, now());
        $this->makeOrder(OrderStatus::DELIVERED, now());

        $response = $this->actingAs($this->agent, 'api')->getJson('/api/v1/admin/logistics/dashboard')->assertOk();

        $steps = collect($response->json('data'))->keyBy('status');
        $this->assertSame(2, $steps[OrderStatus::AWAITING_PAYMENT]['count']);
        $this->assertSame(1, $steps[OrderStatus::QUALITY_CONTROL]['count']);
        $this->assertArrayNotHasKey(OrderStatus::DELIVERED, $steps);
    }

    public function test_alerts_endpoint_returns_orders_past_their_sla(): void
    {
        $late = $this->makeOrder(OrderStatus::AWAITING_PAYMENT, now()->subHours(30));
        $this->makeOrder(OrderStatus::AWAITING_PAYMENT, now()->subHours(1));

        $response = $this->actingAs($this->agent, 'api')->getJson('/api/v1/admin/logistics/alerts')->assertOk();

        $this->assertCount(1, $response->json('data'));
        $this->assertSame($late->id, $response->json('data.0.order.id'));
    }
}
