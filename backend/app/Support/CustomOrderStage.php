<?php

namespace App\Support;

use App\Models\CustomOrderRequest;

/**
 * Étapes simplifiées affichées au client pour un "pedido personalizado"
 * (demandé explicitement par l'utilisateur : Révision → Achat → Réception
 * et contrôle qualité à Madrid → Expédition internationale → Livraison à
 * Nouakchott). Regroupe les 15 statuts détaillés d'OrderStatus (8.4) —
 * inchangés côté commande — en 5 étapes lisibles, sans dupliquer la state
 * machine : ce mapping est purement d'affichage.
 */
final class CustomOrderStage
{
    public const REVIEW = 'review';

    public const PURCHASE = 'purchase';

    public const MADRID_RECEPTION_QC = 'madrid_reception_qc';

    public const INTERNATIONAL_SHIPPING = 'international_shipping';

    public const DELIVERY = 'delivery';

    public static function ordered(): array
    {
        return [self::REVIEW, self::PURCHASE, self::MADRID_RECEPTION_QC, self::INTERNATIONAL_SHIPPING, self::DELIVERY];
    }

    /**
     * Null pour un statut hors parcours normal (rejeté, annulé, remboursé) :
     * pas d'étape à mettre en avant dans la barre de progression, l'état
     * (status_label) suffit à l'expliquer.
     */
    public static function forCustomOrderRequest(CustomOrderRequest $request): ?string
    {
        if ($request->status === CustomOrderRequestStatus::PENDING) {
            return self::REVIEW;
        }

        if ($request->status === CustomOrderRequestStatus::REJECTED) {
            return null;
        }

        $order = $request->relationLoaded('order') ? $request->order : $request->order()->first();

        return $order === null ? self::PURCHASE : self::forOrderStatus($order->status);
    }

    public static function forOrderStatus(string $status): ?string
    {
        return match ($status) {
            OrderStatus::AWAITING_PAYMENT, OrderStatus::PAYMENT_VALIDATED, OrderStatus::PURCHASING,
            OrderStatus::ORDERED_FROM_BOUTIQUE, OrderStatus::SHIPPED_BY_BOUTIQUE => self::PURCHASE,

            OrderStatus::RECEIVED_MADRID, OrderStatus::QUALITY_CONTROL, OrderStatus::CONSOLIDATED => self::MADRID_RECEPTION_QC,

            OrderStatus::SHIPPED_TO_NOUAKCHOTT => self::INTERNATIONAL_SHIPPING,

            OrderStatus::ARRIVED_NOUAKCHOTT, OrderStatus::OUT_FOR_DELIVERY, OrderStatus::DELIVERED => self::DELIVERY,

            default => null,
        };
    }
}
