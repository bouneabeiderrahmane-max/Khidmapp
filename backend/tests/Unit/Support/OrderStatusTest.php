<?php

namespace Tests\Unit\Support;

use App\Support\OrderStatus;
use PHPUnit\Framework\TestCase;

class OrderStatusTest extends TestCase
{
    public function test_forward_sequence_advances_one_step_at_a_time(): void
    {
        $this->assertSame(
            [OrderStatus::PAYMENT_VALIDATED, OrderStatus::CANCELLED],
            OrderStatus::allowedNextStatuses(OrderStatus::AWAITING_PAYMENT),
        );
    }

    public function test_delivered_allows_only_refund(): void
    {
        $this->assertSame([OrderStatus::REFUNDED], OrderStatus::allowedNextStatuses(OrderStatus::DELIVERED));
    }

    public function test_cancelled_allows_only_refund(): void
    {
        $this->assertSame([OrderStatus::REFUNDED], OrderStatus::allowedNextStatuses(OrderStatus::CANCELLED));
    }

    public function test_refunded_is_a_dead_end(): void
    {
        $this->assertSame([], OrderStatus::allowedNextStatuses(OrderStatus::REFUNDED));
    }

    public function test_cancellation_is_allowed_from_any_non_terminal_status(): void
    {
        foreach ([OrderStatus::AWAITING_PAYMENT, OrderStatus::SHIPPED_BY_BOUTIQUE, OrderStatus::OUT_FOR_DELIVERY] as $status) {
            $this->assertContains(OrderStatus::CANCELLED, OrderStatus::allowedNextStatuses($status));
        }
    }

    public function test_free_cancellation_window(): void
    {
        $this->assertTrue(OrderStatus::isFreeCancellation(OrderStatus::ORDERED_FROM_BOUTIQUE));
        $this->assertFalse(OrderStatus::isFreeCancellation(OrderStatus::SHIPPED_BY_BOUTIQUE));
    }

    public function test_terminal_statuses(): void
    {
        $this->assertTrue(OrderStatus::isTerminal(OrderStatus::DELIVERED));
        $this->assertTrue(OrderStatus::isTerminal(OrderStatus::CANCELLED));
        $this->assertTrue(OrderStatus::isTerminal(OrderStatus::REFUNDED));
        $this->assertFalse(OrderStatus::isTerminal(OrderStatus::SHIPPED_BY_BOUTIQUE));
    }
}
