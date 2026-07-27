<?php

namespace Tests\Unit\Services;

use App\Exceptions\Payment\InvalidPaymentAttemptException;
use App\Models\AuditLog;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\Payment\ManualPaymentReviewer;
use App\Support\AuditAction;
use App\Support\OrderActorType;
use App\Support\OrderStatus;
use App\Support\PaymentMethod;
use App\Support\PaymentStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ManualPaymentReviewerTest extends TestCase
{
    use RefreshDatabase;

    private ManualPaymentReviewer $reviewer;

    private User $client;

    private User $agent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->reviewer = app(ManualPaymentReviewer::class);
        $this->client = User::factory()->create();
        $this->agent = User::factory()->create();
        Storage::fake('local');
    }

    private function makeOrder(string $status = OrderStatus::AWAITING_PAYMENT, string $method = PaymentMethod::MANUAL): Order
    {
        return Order::query()->create([
            'user_id' => $this->client->id, 'status' => $status, 'payment_method' => $method,
            'subtotal_eur' => 10, 'exchange_rate_snapshot' => 10, 'subtotal_mru' => 100,
            'delivery_fee_snapshot_mru' => 200, 'total_mru' => 300,
        ]);
    }

    private function pendingProof(Order $order): Payment
    {
        return $this->reviewer->submitProof($order, $this->client, UploadedFile::fake()->image('proof.jpg'));
    }

    public function test_submitting_a_proof_creates_a_pending_payment(): void
    {
        $order = $this->makeOrder();

        $payment = $this->pendingProof($order);

        $this->assertSame(PaymentStatus::PENDING, $payment->status);
        $this->assertSame(PaymentMethod::MANUAL, $payment->method);
        Storage::disk('local')->assertExists($payment->proof_file_path);
    }

    public function test_submitting_a_proof_is_rejected_when_the_order_is_not_awaiting_payment(): void
    {
        $order = $this->makeOrder(OrderStatus::PAYMENT_VALIDATED);

        $this->expectException(InvalidPaymentAttemptException::class);
        $this->pendingProof($order);
    }

    public function test_submitting_a_proof_is_rejected_when_the_order_uses_bankily(): void
    {
        $order = $this->makeOrder(method: PaymentMethod::BANKILY);

        $this->expectException(InvalidPaymentAttemptException::class);
        $this->pendingProof($order);
    }

    public function test_validating_a_pending_proof_transitions_the_order(): void
    {
        $order = $this->makeOrder();
        $payment = $this->pendingProof($order);

        $updated = $this->reviewer->validate($payment, $this->agent);

        $this->assertSame(PaymentStatus::VALIDATED, $updated->status);
        $this->assertSame($this->agent->id, $updated->reviewed_by);
        $this->assertSame(OrderStatus::PAYMENT_VALIDATED, $order->fresh()->status);
        $this->assertSame(OrderActorType::SERVICE_CLIENT, $order->fresh()->statusHistories()->latest()->first()->actor_type);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $this->agent->id,
            'action' => AuditAction::PAYMENT_VALIDATED,
            'subject_type' => $payment->getMorphClass(),
            'subject_id' => $payment->id,
        ]);
    }

    public function test_rejecting_a_pending_proof_requires_a_reason_and_does_not_change_order_status(): void
    {
        $order = $this->makeOrder();
        $payment = $this->pendingProof($order);

        $updated = $this->reviewer->reject($payment, $this->agent, 'Preuve illisible.');

        $this->assertSame(PaymentStatus::REJECTED, $updated->status);
        $this->assertSame('Preuve illisible.', $updated->rejection_reason);
        $this->assertSame(OrderStatus::AWAITING_PAYMENT, $order->fresh()->status);
        $log = AuditLog::query()->where('action', AuditAction::PAYMENT_REJECTED)->firstOrFail();
        $this->assertSame($payment->id, $log->subject_id);
        $this->assertSame('Preuve illisible.', $log->changes['reason']);
    }

    public function test_requesting_more_info_does_not_change_order_status(): void
    {
        $order = $this->makeOrder();
        $payment = $this->pendingProof($order);

        $updated = $this->reviewer->requestMoreInfo($payment, $this->agent, 'Le montant ne correspond pas, merci de renvoyer une preuve.');

        $this->assertSame(PaymentStatus::INFO_REQUESTED, $updated->status);
        $this->assertNotNull($updated->info_requested_at);
        $this->assertSame(OrderStatus::AWAITING_PAYMENT, $order->fresh()->status);
        $log = AuditLog::query()->where('action', AuditAction::PAYMENT_INFO_REQUESTED)->firstOrFail();
        $this->assertSame($payment->id, $log->subject_id);
    }

    public function test_a_client_can_resubmit_after_a_rejection_creating_a_new_payment_row(): void
    {
        $order = $this->makeOrder();
        $first = $this->pendingProof($order);
        $this->reviewer->reject($first, $this->agent, 'Illisible.');

        $second = $this->pendingProof($order);

        $this->assertNotSame($first->id, $second->id);
        $this->assertSame(PaymentStatus::REJECTED, $first->fresh()->status);
        $this->assertSame(PaymentStatus::PENDING, $second->status);
        $this->assertSame(2, $order->payments()->count());
    }

    public function test_an_already_reviewed_proof_cannot_be_reviewed_again(): void
    {
        $order = $this->makeOrder();
        $payment = $this->pendingProof($order);
        $this->reviewer->validate($payment, $this->agent);

        $this->expectException(InvalidPaymentAttemptException::class);
        $this->reviewer->reject($payment, $this->agent, 'Trop tard.');
    }
}
