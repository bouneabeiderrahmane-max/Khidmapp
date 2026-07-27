<?php

namespace App\Support;

/**
 * Les 15 statuts de commande confirmés par le cahier des charges (8.4).
 * "Brouillon" existe pour complétude de l'énumération mais n'est pas
 * utilisé par le parcours client normal : le panier (Cart/CartItem) joue
 * déjà ce rôle de brouillon avant la création effective de la commande, qui
 * démarre directement à AWAITING_PAYMENT lors du passage de commande
 * ("Client valide la commande" — 8.4, ligne 2). Ce choix évite d'avoir des
 * lignes "orders" orphelines pour des paniers jamais finalisés.
 */
final class OrderStatus
{
    public const DRAFT = 'brouillon';

    public const AWAITING_PAYMENT = 'paiement_en_attente';

    public const PAYMENT_VALIDATED = 'paiement_valide';

    public const PURCHASING = 'achat_en_cours';

    public const ORDERED_FROM_BOUTIQUE = 'commande_boutique';

    public const SHIPPED_BY_BOUTIQUE = 'expedie_boutique';

    public const RECEIVED_MADRID = 'recu_madrid';

    public const QUALITY_CONTROL = 'controle_qualite';

    public const CONSOLIDATED = 'consolidation';

    public const SHIPPED_TO_NOUAKCHOTT = 'expedie_nouakchott';

    public const ARRIVED_NOUAKCHOTT = 'arrive_nouakchott';

    public const OUT_FOR_DELIVERY = 'livraison_en_cours';

    public const DELIVERED = 'livre';

    public const CANCELLED = 'annule';

    public const REFUNDED = 'rembourse';

    /**
     * Ordre du cycle normal (8.4). Utilisé pour dériver les transitions
     * "avancer d'une étape" autorisées.
     */
    private const FORWARD_SEQUENCE = [
        self::AWAITING_PAYMENT,
        self::PAYMENT_VALIDATED,
        self::PURCHASING,
        self::ORDERED_FROM_BOUTIQUE,
        self::SHIPPED_BY_BOUTIQUE,
        self::RECEIVED_MADRID,
        self::QUALITY_CONTROL,
        self::CONSOLIDATED,
        self::SHIPPED_TO_NOUAKCHOTT,
        self::ARRIVED_NOUAKCHOTT,
        self::OUT_FOR_DELIVERY,
        self::DELIVERED,
    ];

    /**
     * Statuts antérieurs à "Expédié par la boutique" : annulation possible
     * sans frais (8.4.1). Au-delà, l'annulation reste possible mais peut
     * impliquer des frais ou un retour préalable du produit — Khidmapp ne
     * modélise pas encore la perception de ces frais (Sprint 7 Paiements),
     * l'annulation au-delà de cette fenêtre exige donc un motif et est
     * marquée `cancellation_fee_applicable` pour traitement par le service
     * client.
     */
    private const FREE_CANCELLATION_STATUSES = [
        self::AWAITING_PAYMENT,
        self::PAYMENT_VALIDATED,
        self::PURCHASING,
        self::ORDERED_FROM_BOUTIQUE,
    ];

    private const TERMINAL_STATUSES = [
        self::DELIVERED,
        self::CANCELLED,
        self::REFUNDED,
    ];

    public static function all(): array
    {
        return [
            self::DRAFT,
            ...self::FORWARD_SEQUENCE,
            self::CANCELLED,
            self::REFUNDED,
        ];
    }

    public static function isTerminal(string $status): bool
    {
        return in_array($status, self::TERMINAL_STATUSES, true);
    }

    public static function isFreeCancellation(string $status): bool
    {
        return in_array($status, self::FREE_CANCELLATION_STATUSES, true);
    }

    public static function canCancel(string $status): bool
    {
        return ! self::isTerminal($status);
    }

    /**
     * Statuts vers lesquels une transition est autorisée depuis $status.
     */
    public static function allowedNextStatuses(string $status): array
    {
        if ($status === self::CANCELLED) {
            return [self::REFUNDED];
        }

        if ($status === self::REFUNDED) {
            return [];
        }

        $next = [];
        $index = array_search($status, self::FORWARD_SEQUENCE, true);

        if ($index !== false && $index + 1 < count(self::FORWARD_SEQUENCE)) {
            $next[] = self::FORWARD_SEQUENCE[$index + 1];
        }

        if (self::canCancel($status)) {
            $next[] = self::CANCELLED;
        }

        if ($status === self::DELIVERED) {
            $next[] = self::REFUNDED;
        }

        return $next;
    }
}
