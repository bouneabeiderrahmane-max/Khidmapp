<?php

namespace App\Services\Payment;

use App\Contracts\BankilyGateway;
use App\Exceptions\Payment\InvalidPaymentAttemptException;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\Order\OrderStatusTransitioner;
use App\Support\OrderActorType;
use App\Support\OrderStatus;
use App\Support\PaymentMethod;
use App\Support\PaymentStatus;
use Illuminate\Support\Facades\DB;

/**
 * Paiement automatique Bankily (CDC 8.5.1). Chaque appel d'initiation crée
 * (ou réutilise, s'il y en a déjà une en cours) une transaction ; la
 * confirmation arrive de façon asynchrone via handleWebhook(), qui
 * transitionne la commande vers "Paiement validé" — sans intervention
 * manuelle, comme l'exige le CDC.
 */
class BankilyPaymentService
{
    public function __construct(
        private readonly BankilyGateway $gateway,
        private readonly OrderStatusTransitioner $transitioner,
    ) {}

    public function initiate(Order $order, User $user): Payment
    {
        if ($order->status !== OrderStatus::AWAITING_PAYMENT) {
            throw InvalidPaymentAttemptException::orderNotAwaitingPayment();
        }

        if ($order->payment_method !== PaymentMethod::BANKILY) {
            throw InvalidPaymentAttemptException::wrongMethod(PaymentMethod::BANKILY);
        }

        $pending = $order->payments()
            ->where('method', PaymentMethod::BANKILY)
            ->where('status', PaymentStatus::PENDING)
            ->latest()
            ->first();

        if ($pending !== null) {
            return $pending;
        }

        $result = $this->gateway->initiate($order);

        return Payment::query()->create([
            'order_id' => $order->id,
            'submitted_by' => $user->id,
            'method' => PaymentMethod::BANKILY,
            'status' => PaymentStatus::PENDING,
            'amount_mru' => $order->total_mru,
            'external_reference' => $result['reference'],
            'initiated_at' => now(),
        ]);
    }

    /**
     * @param  array{reference: string, status: string, failure_reason?: string|null}  $payload
     */
    public function handleWebhook(array $payload): Payment
    {
        $payment = Payment::query()
            ->where('method', PaymentMethod::BANKILY)
            ->where('external_reference', $payload['reference'])
            ->firstOrFail();

        // Idempotence : une notification rejouée par Bankily (ou reçue en
        // double) ne doit pas retraiter une transaction déjà terminale.
        if (PaymentStatus::isTerminal($payment->status)) {
            return $payment;
        }

        $newStatus = match ($payload['status']) {
            'succeeded' => PaymentStatus::VALIDATED,
            'expired' => PaymentStatus::EXPIRED,
            'cancelled' => PaymentStatus::CANCELLED,
            default => PaymentStatus::FAILED,
        };

        DB::transaction(function () use ($payment, $newStatus, $payload) {
            $payment->update([
                'status' => $newStatus,
                'raw_payload' => $payload,
                'failure_reason' => $newStatus === PaymentStatus::VALIDATED ? null : ($payload['failure_reason'] ?? null),
                'confirmed_at' => now(),
            ]);

            $order = $payment->order;

            if ($newStatus === PaymentStatus::VALIDATED && $order->status === OrderStatus::AWAITING_PAYMENT) {
                $this->transitioner->transition($order, OrderStatus::PAYMENT_VALIDATED, OrderActorType::SYSTEM);
            }
        });

        return $payment->fresh();
    }
}
