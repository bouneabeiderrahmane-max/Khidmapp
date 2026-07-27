<?php

namespace Tests\Feature\Payment;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Support\OrderStatus;
use App\Support\PaymentMethod;
use App\Support\PaymentStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BankilyWebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    private function makePendingPayment(): Payment
    {
        $client = User::factory()->create();
        $order = Order::query()->create([
            'user_id' => $client->id, 'status' => OrderStatus::AWAITING_PAYMENT, 'payment_method' => PaymentMethod::BANKILY,
            'subtotal_eur' => 10, 'exchange_rate_snapshot' => 10, 'subtotal_mru' => 100,
            'delivery_fee_snapshot_mru' => 200, 'total_mru' => 300,
        ]);

        return Payment::query()->create([
            'order_id' => $order->id, 'submitted_by' => $client->id, 'method' => PaymentMethod::BANKILY,
            'status' => PaymentStatus::PENDING, 'amount_mru' => 300, 'external_reference' => 'BKY-TEST-1', 'initiated_at' => now(),
        ]);
    }

    public function test_a_webhook_without_a_valid_signature_is_rejected(): void
    {
        $payment = $this->makePendingPayment();

        $this->postJson('/api/v1/webhooks/bankily', ['reference' => $payment->external_reference, 'status' => 'succeeded'])
            ->assertStatus(401);

        $this->assertSame(PaymentStatus::PENDING, $payment->fresh()->status);
    }

    public function test_a_webhook_with_a_valid_signature_confirms_the_payment_and_the_order(): void
    {
        $payment = $this->makePendingPayment();

        $this->postJson(
            '/api/v1/webhooks/bankily',
            ['reference' => $payment->external_reference, 'status' => 'succeeded'],
            ['X-Bankily-Signature' => config('payments.bankily.webhook_secret')],
        )->assertOk();

        $this->assertSame(PaymentStatus::VALIDATED, $payment->fresh()->status);
        $this->assertSame(OrderStatus::PAYMENT_VALIDATED, $payment->fresh()->order->status);
    }

    public function test_a_webhook_for_an_unknown_reference_returns_a_404(): void
    {
        $this->postJson(
            '/api/v1/webhooks/bankily',
            ['reference' => 'BKY-UNKNOWN', 'status' => 'succeeded'],
            ['X-Bankily-Signature' => config('payments.bankily.webhook_secret')],
        )->assertNotFound();
    }
}
