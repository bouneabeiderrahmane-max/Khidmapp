<?php

namespace App\Support;

/**
 * Les moments clés du parcours de commande déclenchant une notification
 * automatique (CDC 8.7). "Annulation ou remboursement" est une seule
 * puce du cahier des charges mais correspond à deux statuts distincts de
 * la state machine (8.4) : conservés comme deux gabarits séparés.
 */
final class NotificationTemplate
{
    public const ORDER_CONFIRMED = 'order_confirmed';

    public const PAYMENT_VALIDATED = 'payment_validated';

    public const PURCHASED_FROM_BOUTIQUE = 'purchased_from_boutique';

    public const RECEIVED_MADRID = 'received_madrid';

    public const QUALITY_CONTROL_ANOMALY = 'quality_control_anomaly';

    public const SHIPPED_TO_NOUAKCHOTT = 'shipped_to_nouakchott';

    public const ARRIVED_NOUAKCHOTT = 'arrived_nouakchott';

    public const OUT_FOR_DELIVERY = 'out_for_delivery';

    public const DELIVERED = 'delivered';

    public const ORDER_CANCELLED = 'order_cancelled';

    public const ORDER_REFUNDED = 'order_refunded';

    /**
     * Extension au-delà des 10 événements listés en 8.7 : une réponse
     * d'agent sur une réclamation (module 8.8, Sprint 10) prévient le
     * client qu'il a une réponse à consulter — non critique, push
     * uniquement (voir config/notifications.php).
     */
    public const COMPLAINT_REPLY = 'complaint_reply';
}
