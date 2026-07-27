<?php

namespace Tests\Unit\Services;

use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\User;
use App\Services\Logistics\LogisticsAlertService;
use App\Support\OrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogisticsAlertServiceTest extends TestCase
{
    use RefreshDatabase;

    private LogisticsAlertService $service;

    private User $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(LogisticsAlertService::class);
        $this->client = User::factory()->create();
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

    public function test_an_order_within_its_sla_produces_no_alert(): void
    {
        // paiement_en_attente : SLA 24h — entré il y a 2h.
        $this->makeOrder(OrderStatus::AWAITING_PAYMENT, now()->subHours(2));

        $this->assertCount(0, $this->service->currentAlerts());
    }

    public function test_an_order_past_its_sla_produces_an_alert(): void
    {
        $order = $this->makeOrder(OrderStatus::AWAITING_PAYMENT, now()->subHours(30));

        $alerts = $this->service->currentAlerts();

        $this->assertCount(1, $alerts);
        $this->assertSame($order->id, $alerts->first()['order']->id);
        $this->assertSame(24, $alerts->first()['sla_hours']);
        $this->assertEqualsWithDelta(6.0, $alerts->first()['overrun_hours'], 0.1);
    }

    public function test_terminal_orders_never_produce_an_alert(): void
    {
        $this->makeOrder(OrderStatus::DELIVERED, now()->subDays(30));
        $this->makeOrder(OrderStatus::CANCELLED, now()->subDays(30));
        $this->makeOrder(OrderStatus::REFUNDED, now()->subDays(30));

        $this->assertCount(0, $this->service->currentAlerts());
    }

    public function test_alerts_are_sorted_by_overrun_descending(): void
    {
        $small = $this->makeOrder(OrderStatus::AWAITING_PAYMENT, now()->subHours(26));
        $large = $this->makeOrder(OrderStatus::PAYMENT_VALIDATED, now()->subHours(20));

        $alerts = $this->service->currentAlerts();

        $this->assertSame($large->id, $alerts->first()['order']->id);
        $this->assertSame($small->id, $alerts->last()['order']->id);
    }
}
