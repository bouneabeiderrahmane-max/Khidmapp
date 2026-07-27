<?php

namespace App\Services\Payment;

use App\Exceptions\Payment\InvalidPaymentAttemptException;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\Order\OrderStatusTransitioner;
use App\Support\OrderActorType;
use App\Support\OrderStatus;
use App\Support\PaymentMethod;
use App\Support\PaymentStatus;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Paiement manuel par virement (CDC 8.5.2) : soumission d'une preuve par le
 * client, puis revue par le service client/administrateur (valider,
 * refuser avec motif obligatoire, ou demander un complément sans changer
 * le statut de la commande).
 */
class ManualPaymentReviewer
{
    public function __construct(private readonly OrderStatusTransitioner $transitioner) {}

    public function submitProof(Order $order, User $user, UploadedFile $file): Payment
    {
        if ($order->status !== OrderStatus::AWAITING_PAYMENT) {
            throw InvalidPaymentAttemptException::orderNotAwaitingPayment();
        }

        if ($order->payment_method !== PaymentMethod::MANUAL) {
            throw InvalidPaymentAttemptException::wrongMethod(PaymentMethod::MANUAL);
        }

        $path = $file->store('payment-proofs');

        return Payment::query()->create([
            'order_id' => $order->id,
            'submitted_by' => $user->id,
            'method' => PaymentMethod::MANUAL,
            'status' => PaymentStatus::PENDING,
            'amount_mru' => $order->total_mru,
            'proof_file_path' => $path,
            'initiated_at' => now(),
        ]);
    }

    public function validate(Payment $payment, User $agent): Payment
    {
        $this->guardPending($payment);

        return DB::transaction(function () use ($payment, $agent) {
            $payment->update([
                'status' => PaymentStatus::VALIDATED,
                'reviewed_by' => $agent->id,
                'reviewed_at' => now(),
                'confirmed_at' => now(),
            ]);

            $order = $payment->order;

            if ($order->status === OrderStatus::AWAITING_PAYMENT) {
                $this->transitioner->transition($order, OrderStatus::PAYMENT_VALIDATED, OrderActorType::forAgent($agent), $agent->id);
            }

            return $payment->fresh();
        });
    }

    public function reject(Payment $payment, User $agent, string $reason): Payment
    {
        $this->guardPending($payment);

        $payment->update([
            'status' => PaymentStatus::REJECTED,
            'rejection_reason' => $reason,
            'reviewed_by' => $agent->id,
            'reviewed_at' => now(),
        ]);

        return $payment->fresh();
    }

    public function requestMoreInfo(Payment $payment, User $agent, string $note): Payment
    {
        $this->guardPending($payment);

        $payment->update([
            'status' => PaymentStatus::INFO_REQUESTED,
            'info_requested_note' => $note,
            'info_requested_at' => now(),
            'reviewed_by' => $agent->id,
            'reviewed_at' => now(),
        ]);

        return $payment->fresh();
    }

    private function guardPending(Payment $payment): void
    {
        if ($payment->status !== PaymentStatus::PENDING) {
            throw InvalidPaymentAttemptException::proofAlreadyReviewed();
        }
    }
}
