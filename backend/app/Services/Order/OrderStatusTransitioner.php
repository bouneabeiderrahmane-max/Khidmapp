<?php

namespace App\Services\Order;

use App\Exceptions\Order\InvalidOrderTransitionException;
use App\Exceptions\Order\MissingTransitionNoteException;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Services\Notification\NotificationService;
use App\Support\OrderStatus;
use Illuminate\Support\Facades\DB;

/**
 * State machine des 15 statuts de commande (CDC 8.4). Chaque transition est
 * validée contre OrderStatus::allowedNextStatuses() et journalisée dans
 * order_status_histories (8.4.1 : traçabilité complète, horodatage +
 * acteur). Déclenche, le cas échéant, la notification client associée au
 * nouveau statut (8.7).
 */
class OrderStatusTransitioner
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function transition(
        Order $order,
        string $toStatus,
        string $actorType,
        ?int $actorId = null,
        ?string $note = null,
    ): Order {
        $fromStatus = $order->status;
        $allowed = OrderStatus::allowedNextStatuses($fromStatus);

        if (! in_array($toStatus, $allowed, true)) {
            throw InvalidOrderTransitionException::from($fromStatus, $toStatus);
        }

        if ($toStatus === OrderStatus::REFUNDED && trim((string) $note) === '') {
            throw MissingTransitionNoteException::forRefund();
        }

        $cancellationFeeApplicable = false;

        if ($toStatus === OrderStatus::CANCELLED) {
            $cancellationFeeApplicable = ! OrderStatus::isFreeCancellation($fromStatus);

            if ($cancellationFeeApplicable && trim((string) $note) === '') {
                throw MissingTransitionNoteException::forLateCancellation();
            }
        }

        DB::transaction(function () use ($order, $toStatus, $fromStatus, $actorType, $actorId, $note, $cancellationFeeApplicable) {
            $order->status = $toStatus;

            if ($toStatus === OrderStatus::CANCELLED) {
                $order->cancelled_at = now();
                $order->cancellation_reason = $note;
                $order->cancellation_fee_applicable = $cancellationFeeApplicable;
            }

            if ($toStatus === OrderStatus::REFUNDED) {
                $order->refunded_at = now();
                $order->refund_reason = $note;
            }

            $order->save();

            OrderStatusHistory::query()->create([
                'order_id' => $order->id,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'actor_type' => $actorType,
                'actor_id' => $actorId,
                'note' => $note,
            ]);
        });

        $order = $order->fresh();

        $templateKey = config('notifications.order_status_templates')[$toStatus] ?? null;

        if ($templateKey !== null) {
            $this->notifications->notify($order->user, $templateKey, ['order_id' => $order->id, 'total' => $order->total_mru]);
        }

        return $order;
    }
}
