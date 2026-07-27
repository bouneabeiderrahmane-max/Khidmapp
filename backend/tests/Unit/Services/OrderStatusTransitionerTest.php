<?php

namespace Tests\Unit\Services;

use App\Exceptions\Order\InvalidOrderTransitionException;
use App\Exceptions\Order\MissingTransitionNoteException;
use App\Models\Order;
use App\Models\User;
use App\Services\Order\OrderStatusTransitioner;
use App\Support\OrderActorType;
use App\Support\OrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderStatusTransitionerTest extends TestCase
{
    use RefreshDatabase;

    private OrderStatusTransitioner $transitioner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->transitioner = app(OrderStatusTransitioner::class);
    }

    private function makeOrder(string $status = OrderStatus::AWAITING_PAYMENT): Order
    {
        $user = User::factory()->create();

        return Order::query()->create([
            'user_id' => $user->id,
            'status' => $status,
            'payment_method' => 'manual',
            'subtotal_eur' => 10,
            'exchange_rate_snapshot' => 47.5,
            'subtotal_mru' => 475,
            'delivery_fee_snapshot_mru' => 350,
            'total_mru' => 825,
        ]);
    }

    public function test_a_legal_transition_updates_status_and_logs_history(): void
    {
        $order = $this->makeOrder();

        $order = $this->transitioner->transition($order, OrderStatus::PAYMENT_VALIDATED, OrderActorType::ADMINISTRATEUR, 1);

        $this->assertSame(OrderStatus::PAYMENT_VALIDATED, $order->status);
        $this->assertCount(1, $order->statusHistories);
        $this->assertSame(OrderStatus::AWAITING_PAYMENT, $order->statusHistories->first()->from_status);
        $this->assertSame(OrderStatus::PAYMENT_VALIDATED, $order->statusHistories->first()->to_status);
    }

    public function test_an_illegal_transition_is_rejected(): void
    {
        $order = $this->makeOrder(OrderStatus::PAYMENT_VALIDATED);

        $this->expectException(InvalidOrderTransitionException::class);
        $this->transitioner->transition($order, OrderStatus::DELIVERED, OrderActorType::ADMINISTRATEUR, 1);
    }

    public function test_refund_requires_a_note(): void
    {
        $order = $this->makeOrder(OrderStatus::DELIVERED);

        $this->expectException(MissingTransitionNoteException::class);
        $this->transitioner->transition($order, OrderStatus::REFUNDED, OrderActorType::ADMINISTRATEUR, 1);
    }

    public function test_refund_succeeds_with_a_note(): void
    {
        $order = $this->makeOrder(OrderStatus::DELIVERED);

        $order = $this->transitioner->transition($order, OrderStatus::REFUNDED, OrderActorType::ADMINISTRATEUR, 1, 'Article défectueux.');

        $this->assertSame(OrderStatus::REFUNDED, $order->status);
        $this->assertNotNull($order->refunded_at);
        $this->assertSame('Article défectueux.', $order->refund_reason);
    }

    public function test_cancellation_within_the_free_window_needs_no_note_and_carries_no_fee(): void
    {
        $order = $this->makeOrder(OrderStatus::AWAITING_PAYMENT);

        $order = $this->transitioner->transition($order, OrderStatus::CANCELLED, OrderActorType::CLIENT, 1);

        $this->assertSame(OrderStatus::CANCELLED, $order->status);
        $this->assertFalse($order->cancellation_fee_applicable);
        $this->assertNotNull($order->cancelled_at);
    }

    public function test_cancellation_past_the_free_window_requires_a_note(): void
    {
        $order = $this->makeOrder(OrderStatus::SHIPPED_BY_BOUTIQUE);

        $this->expectException(MissingTransitionNoteException::class);
        $this->transitioner->transition($order, OrderStatus::CANCELLED, OrderActorType::ADMINISTRATEUR, 1);
    }

    public function test_cancellation_past_the_free_window_succeeds_with_a_note_and_flags_the_fee(): void
    {
        $order = $this->makeOrder(OrderStatus::SHIPPED_BY_BOUTIQUE);

        $order = $this->transitioner->transition(
            $order,
            OrderStatus::CANCELLED,
            OrderActorType::ADMINISTRATEUR,
            1,
            'Colis déjà expédié.',
        );

        $this->assertTrue($order->cancellation_fee_applicable);
        $this->assertSame('Colis déjà expédié.', $order->cancellation_reason);
    }
}
