<?php

namespace Tests\Unit\Services;

use App\Exceptions\Payment\InvalidPaymentAttemptException;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\Payment\BankilyPaymentService;
use App\Support\OrderStatus;
use App\Support\PaymentMethod;
use App\Support\PaymentStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BankilyPaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    private BankilyPaymentService $service;

    private User $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(BankilyPaymentService::class);
        $this->client = User::factory()->create();
    }

    private function makeOrder(string $status = OrderStatus::AWAITING_PAYMENT, string $method = PaymentMethod::BANKILY): Order
    {
        return Order::query()->create([
            'user_id' => $this->client->id, 'status' => $status, 'payment_method' => $method,
            'subtotal_eur' => 10, 'exchange_rate_snapshot' => 10, 'subtotal_mru' => 100,
            'delivery_fee_snapshot_mru' => 200, 'total_mru' => 300,
        ]);
    }

    public function test_it_initiates_a_payment_with_a_reference(): void
    {
        $order = $this->makeOrder();

        $payment = $this->service->initiate($order, $this->client);

        $this->assertSame(PaymentMethod::BANKILY, $payment->method);
        $this->assertSame(PaymentStatus::PENDING, $payment->status);
        $this->assertNotNull($payment->external_reference);
        $this->assertSame(300.0, (float) $payment->amount_mru);
    }

    public function test_it_reuses_a_pending_transaction_instead_of_creating_a_duplicate(): void
    {
        $order = $this->makeOrder();

        $first = $this->service->initiate($order, $this->client);
        $second = $this->service->initiate($order, $this->client);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, Payment::query()->count());
    }

    public function test_it_rejects_initiation_when_the_order_is_not_awaiting_payment(): void
    {
        $order = $this->makeOrder(OrderStatus::PAYMENT_VALIDATED);

        $this->expectException(InvalidPaymentAttemptException::class);
        $this->service->initiate($order, $this->client);
    }

    public function test_it_rejects_initiation_when_the_order_uses_manual_payment(): void
    {
        $order = $this->makeOrder(method: PaymentMethod::MANUAL);

        $this->expectException(InvalidPaymentAttemptException::class);
        $this->service->initiate($order, $this->client);
    }

    public function test_a_successful_webhook_validates_the_payment_and_the_order(): void
    {
        $order = $this->makeOrder();
        $payment = $this->service->initiate($order, $this->client);

        $updated = $this->service->handleWebhook(['reference' => $payment->external_reference, 'status' => 'succeeded']);

        $this->assertSame(PaymentStatus::VALIDATED, $updated->status);
        $this->assertSame(OrderStatus::PAYMENT_VALIDATED, $order->fresh()->status);
    }

    public function test_a_failed_webhook_marks_the_payment_failed_without_touching_the_order(): void
    {
        $order = $this->makeOrder();
        $payment = $this->service->initiate($order, $this->client);

        $updated = $this->service->handleWebhook(['reference' => $payment->external_reference, 'status' => 'failed', 'failure_reason' => 'insufficient_funds']);

        $this->assertSame(PaymentStatus::FAILED, $updated->status);
        $this->assertSame('insufficient_funds', $updated->failure_reason);
        $this->assertSame(OrderStatus::AWAITING_PAYMENT, $order->fresh()->status);
    }

    public function test_a_webhook_is_idempotent_for_an_already_terminal_payment(): void
    {
        $order = $this->makeOrder();
        $payment = $this->service->initiate($order, $this->client);

        $this->service->handleWebhook(['reference' => $payment->external_reference, 'status' => 'succeeded']);
        // Replayed notification: must not re-fire the order transition (which would throw, since
        // the order is no longer awaiting payment) nor change the payment's confirmed_at again.
        $secondPass = $this->service->handleWebhook(['reference' => $payment->external_reference, 'status' => 'succeeded']);

        $this->assertSame(PaymentStatus::VALIDATED, $secondPass->status);
        $this->assertSame(OrderStatus::PAYMENT_VALIDATED, $order->fresh()->status);
    }

    public function test_a_late_webhook_does_not_reactivate_an_order_that_left_awaiting_payment(): void
    {
        $order = $this->makeOrder();
        $payment = $this->service->initiate($order, $this->client);

        // The order was cancelled in the meantime (e.g. by the client) before Bankily's
        // notification arrived.
        $order->update(['status' => OrderStatus::CANCELLED]);

        $updated = $this->service->handleWebhook(['reference' => $payment->external_reference, 'status' => 'succeeded']);

        $this->assertSame(PaymentStatus::VALIDATED, $updated->status);
        $this->assertSame(OrderStatus::CANCELLED, $order->fresh()->status);
    }
}
