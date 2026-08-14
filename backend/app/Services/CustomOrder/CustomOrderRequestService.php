<?php

namespace App\Services\CustomOrder;

use App\Models\Address;
use App\Models\CustomOrderRequest;
use App\Models\Order;
use App\Models\User;
use App\Services\Notification\NotificationService;
use App\Services\Pricing\CustomOrderPricingCalculator;
use App\Support\CustomOrderRequestStatus;
use App\Support\NotificationTemplate;
use App\Support\OrderActorType;
use App\Support\OrderStatus;
use Illuminate\Support\Facades\DB;

/**
 * "Pedido personalizado" (CDC — extension signalée, clarifiée avec
 * l'utilisateur) : le client soumet un ou plusieurs articles trouvés hors
 * catalogue (URL produit + prix estimé, engageant) ; la demande reste
 * "en_attente" jusqu'à revue manuelle de faisabilité par l'administration.
 * L'approbation seule crée la Commande réelle — jamais la soumission
 * elle-même — pour ne pas engager le client avant confirmation (paiement
 * collecté après confirmation, clarifié avec l'utilisateur).
 */
class CustomOrderRequestService
{
    public function __construct(
        private readonly CustomOrderPricingCalculator $calculator,
        private readonly NotificationService $notifications,
    ) {}

    /**
     * Faisabilité confirmée : calcule le prix final (moteur de marge
     * standard, précédence catégorie > boutique > global — sans catégorie
     * ici, donc boutique > global) à partir des prix estimés par le client,
     * puis crée la Commande au statut initial habituel (8.4 : "Client
     * valide la commande"), comme un checkout classique.
     */
    public function approve(CustomOrderRequest $request, User $admin, ?string $note = null): CustomOrderRequest
    {
        abort_unless($request->status === CustomOrderRequestStatus::PENDING, 422, __('khidmapp.custom_order_not_pending'));

        $request->loadMissing('items');
        $address = Address::query()->findOrFail($request->address_id);
        $totals = $this->calculator->calculate($request->items);

        $order = DB::transaction(function () use ($request, $admin, $address, $totals, $note) {
            $order = Order::query()->create([
                'user_id' => $request->user_id,
                'status' => OrderStatus::AWAITING_PAYMENT,
                'address_id' => $address->id,
                'shipping_label' => $address->label,
                'shipping_city' => $address->city,
                'shipping_area' => $address->area,
                'shipping_phone' => $address->phone,
                'payment_method' => $request->payment_method,
                'subtotal_eur' => $totals->subtotalEur,
                'exchange_rate_snapshot' => $totals->exchangeRate,
                'subtotal_mru' => $totals->subtotalMru,
                'delivery_fee_snapshot_mru' => $totals->deliveryFeeMru,
                'delivery_zone' => $totals->deliveryZone,
                'total_mru' => $totals->totalMru,
            ]);

            foreach ($totals->lines as $line) {
                $customItem = $line['item'];
                $breakdown = $line['breakdown'];

                $order->items()->create([
                    'boutique_id' => $customItem->boutique_id,
                    'custom_order_item_id' => $customItem->id,
                    'product_name_snapshot' => [
                        'fr' => 'Article personnalisé',
                        'ar' => 'منتج مخصص',
                    ],
                    'quantity' => $customItem->quantity,
                    'unit_price_eur' => $breakdown->basePriceEur,
                    'unit_price_mru_snapshot' => $breakdown->subtotalMru,
                    'margin_percent_snapshot' => $breakdown->marginPercent,
                    'margin_amount_mru_snapshot' => $breakdown->marginAmountMru,
                    'margin_source_snapshot' => $breakdown->marginSource,
                ]);
            }

            $order->statusHistories()->create([
                'from_status' => null,
                'to_status' => OrderStatus::AWAITING_PAYMENT,
                'actor_type' => OrderActorType::forAgent($admin),
                'actor_id' => $admin->id,
                'note' => 'Commande créée depuis une demande de produit personnalisé confirmée.',
            ]);

            $request->update([
                'status' => CustomOrderRequestStatus::APPROVED,
                'order_id' => $order->id,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
                'admin_note' => $note,
            ]);

            return $order;
        });

        $this->notifications->notify($order->user, NotificationTemplate::ORDER_CONFIRMED, ['order_id' => $order->id, 'total' => $order->total_mru]);

        return $request->fresh(['items', 'order']);
    }

    public function reject(CustomOrderRequest $request, User $admin, string $reason): CustomOrderRequest
    {
        abort_unless($request->status === CustomOrderRequestStatus::PENDING, 422, __('khidmapp.custom_order_not_pending'));

        $request->update([
            'status' => CustomOrderRequestStatus::REJECTED,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
            'admin_note' => $reason,
        ]);

        $this->notifications->notify($request->user, NotificationTemplate::CUSTOM_ORDER_REJECTED, ['request_id' => $request->id, 'reason' => $reason]);

        return $request->fresh(['items']);
    }
}
