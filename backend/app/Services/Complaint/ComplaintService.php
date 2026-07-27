<?php

namespace App\Services\Complaint;

use App\Exceptions\Complaint\InvalidComplaintTransitionException;
use App\Models\Complaint;
use App\Models\ComplaintMessage;
use App\Models\Order;
use App\Models\User;
use App\Support\ComplaintStatus;
use App\Support\OrderActorType;
use Illuminate\Support\Facades\DB;

/**
 * Réclamations (CDC 8.8) : ouverture rattachée à une commande, fil de
 * discussion avec pièces jointes, cycle de statuts. Le CDC ne détaille pas
 * de table de transitions comme pour les commandes (8.4) — la "prise en
 * charge" est ici interprétée comme la première réponse d'un agent sur
 * une réclamation "ouverte" (transition automatique vers "en_cours"),
 * et une réponse du client sur une réclamation "en_attente_client" la
 * remet automatiquement "en_cours". Choix documentés dans docs/PLAN.md.
 */
class ComplaintService
{
    /**
     * @param  array<int, string>  $attachments
     */
    public function open(User $client, Order $order, string $category, string $message, array $attachments = []): Complaint
    {
        return DB::transaction(function () use ($client, $order, $category, $message, $attachments) {
            $complaint = Complaint::query()->create([
                'order_id' => $order->id,
                'user_id' => $client->id,
                'category' => $category,
                'status' => ComplaintStatus::OUVERTE,
            ]);

            $complaint->messages()->create([
                'sender_type' => OrderActorType::CLIENT,
                'sender_id' => $client->id,
                'message' => $message,
                'attachments' => $attachments,
            ]);

            return $complaint;
        });
    }

    /**
     * Ouverture automatique d'une réclamation interne (CDC 8.6.1 : une
     * anomalie de contrôle qualité "ouvre automatiquement une réclamation
     * interne"). Le message initial n'a pas d'auteur humain — sender_id
     * reste null, sender_type = "system".
     */
    public function openAutomatically(Order $order, string $category, string $message): Complaint
    {
        return DB::transaction(function () use ($order, $category, $message) {
            $complaint = Complaint::query()->create([
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'category' => $category,
                'status' => ComplaintStatus::OUVERTE,
            ]);

            $complaint->messages()->create([
                'sender_type' => OrderActorType::SYSTEM,
                'sender_id' => null,
                'message' => $message,
            ]);

            return $complaint;
        });
    }

    /**
     * @param  array<int, string>  $attachments
     */
    public function addMessage(Complaint $complaint, User $sender, string $senderType, ?string $message, array $attachments = []): ComplaintMessage
    {
        return DB::transaction(function () use ($complaint, $sender, $senderType, $message, $attachments) {
            $complaintMessage = $complaint->messages()->create([
                'sender_type' => $senderType,
                'sender_id' => $sender->id,
                'message' => $message,
                'attachments' => $attachments,
            ]);

            if ($senderType === OrderActorType::CLIENT && $complaint->status === ComplaintStatus::EN_ATTENTE_CLIENT) {
                $complaint->update(['status' => ComplaintStatus::EN_COURS]);
            } elseif (
                in_array($senderType, [OrderActorType::SERVICE_CLIENT, OrderActorType::ADMINISTRATEUR], true)
                && $complaint->status === ComplaintStatus::OUVERTE
            ) {
                $complaint->update(['status' => ComplaintStatus::EN_COURS]);
            }

            return $complaintMessage;
        });
    }

    public function updateStatus(Complaint $complaint, string $toStatus): Complaint
    {
        if (! in_array($toStatus, ComplaintStatus::allowedNextStatuses($complaint->status), true)) {
            throw InvalidComplaintTransitionException::from($complaint->status, $toStatus);
        }

        $complaint->update(['status' => $toStatus]);

        return $complaint->fresh();
    }
}
