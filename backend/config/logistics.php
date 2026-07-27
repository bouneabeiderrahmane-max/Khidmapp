<?php

use App\Support\OrderStatus;

return [

    /*
    |--------------------------------------------------------------------------
    | Délais indicatifs par statut (CDC 8.6.2)
    |--------------------------------------------------------------------------
    |
    | "Chaque étape dispose d'un délai indicatif ; tout dépassement
    | significatif déclenche une alerte interne." Le cahier des charges ne
    | fournit de chiffre exact que pour deux étapes très en amont
    | (confirmation Bankily < 30 s, traitement d'une preuve manuelle < 24 h
    | ouvrées — voir indicateurs §"Délai de confirmation de paiement") et
    | précise explicitement que le délai boutique → Madrid varie "selon
    | boutique" sans cible fixe ("suivi et alerté si dépassement", sans
    | autre précision). Il n'existe donc aucune donnée logistique réelle
    | (par boutique/étape/transporteur) pour les huit autres étapes.
    |
    | Les valeurs ci-dessous sont des placeholders indicatifs raisonnables
    | (pas des données mesurées), au même titre que l'estimation statique de
    | livraison du Sprint 5 (config/catalog.php) — à remplacer dès que de
    | vraies données opérationnelles existent, idéalement différenciées par
    | boutique/transporteur.
    |
    */

    'step_sla_hours' => [
        OrderStatus::AWAITING_PAYMENT => 24,
        OrderStatus::PAYMENT_VALIDATED => 4,
        OrderStatus::PURCHASING => 24,
        OrderStatus::ORDERED_FROM_BOUTIQUE => 48,
        OrderStatus::SHIPPED_BY_BOUTIQUE => 120,
        OrderStatus::RECEIVED_MADRID => 24,
        OrderStatus::QUALITY_CONTROL => 24,
        OrderStatus::CONSOLIDATED => 48,
        OrderStatus::SHIPPED_TO_NOUAKCHOTT => 240,
        OrderStatus::ARRIVED_NOUAKCHOTT => 24,
        OrderStatus::OUT_FOR_DELIVERY => 48,
    ],

];
