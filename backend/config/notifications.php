<?php

use App\Support\NotificationTemplate;
use App\Support\OrderStatus;

return [

    /*
    |--------------------------------------------------------------------------
    | Gabarit déclenché par statut de commande (CDC 8.7)
    |--------------------------------------------------------------------------
    |
    | Seuls les statuts explicitement listés par le CDC déclenchent une
    | notification automatique lors de la transition — les statuts absents
    | de cette liste (ex. achat_en_cours, expedie_boutique, consolidation)
    | sont des étapes internes sans notification client dédiée. La
    | "confirmation de commande" (statut initial, jamais atteint via une
    | transition) et l'anomalie de contrôle qualité sont déclenchées
    | ailleurs (respectivement à la création de la commande et dans
    | QualityControlController).
    |
    */

    'order_status_templates' => [
        OrderStatus::PAYMENT_VALIDATED => NotificationTemplate::PAYMENT_VALIDATED,
        OrderStatus::ORDERED_FROM_BOUTIQUE => NotificationTemplate::PURCHASED_FROM_BOUTIQUE,
        OrderStatus::RECEIVED_MADRID => NotificationTemplate::RECEIVED_MADRID,
        OrderStatus::SHIPPED_TO_NOUAKCHOTT => NotificationTemplate::SHIPPED_TO_NOUAKCHOTT,
        OrderStatus::ARRIVED_NOUAKCHOTT => NotificationTemplate::ARRIVED_NOUAKCHOTT,
        OrderStatus::OUT_FOR_DELIVERY => NotificationTemplate::OUT_FOR_DELIVERY,
        OrderStatus::DELIVERED => NotificationTemplate::DELIVERED,
        OrderStatus::CANCELLED => NotificationTemplate::ORDER_CANCELLED,
        OrderStatus::REFUNDED => NotificationTemplate::ORDER_REFUNDED,
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications critiques (CDC 8.7.2)
    |--------------------------------------------------------------------------
    |
    | "Le client peut configurer ses préférences de notification par canal
    | (...) dans les limites imposées par les notifications critiques (ex. :
    | la confirmation de paiement ne peut pas être totalement désactivée)."
    | Le CDC ne donne qu'un seul exemple explicite (paiement) ; annulation et
    | remboursement sont ajoutés ici par extension raisonnable (événements à
    | impact financier direct pour le client), à confirmer si besoin.
    |
    | Un gabarit critique est toujours envoyé sur au moins un canal (push,
    | le seul sans coût/dépendance externe) même si le client a désactivé
    | tous ses canaux.
    |
    */

    'critical_templates' => [
        NotificationTemplate::PAYMENT_VALIDATED,
        NotificationTemplate::ORDER_CANCELLED,
        NotificationTemplate::ORDER_REFUNDED,
    ],

    /*
    |--------------------------------------------------------------------------
    | Éligibilité par canal (CDC 8.7.1)
    |--------------------------------------------------------------------------
    |
    | Push : canal par défaut, tenté pour tous les gabarits.
    | SMS : "pour les événements critiques (paiement, livraison)" — restreint
    | ci-dessous à ces événements précis, pas à tous les gabarits.
    | E-mail : "pour les récapitulatifs de commande et factures" — restreint
    | à la confirmation de commande (aucun système de facture séparé).
    |
    */

    'sms_eligible_templates' => [
        NotificationTemplate::PAYMENT_VALIDATED,
        NotificationTemplate::OUT_FOR_DELIVERY,
        NotificationTemplate::DELIVERED,
        NotificationTemplate::ORDER_CANCELLED,
        NotificationTemplate::ORDER_REFUNDED,
    ],

    'email_eligible_templates' => [
        NotificationTemplate::ORDER_CONFIRMED,
    ],

];
