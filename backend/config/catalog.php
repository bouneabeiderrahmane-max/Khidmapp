<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Catalogue & recherche (CDC 7.2.2, 8.3)
    |--------------------------------------------------------------------------
    |
    | delivery_estimate_days_* : estimation affichée sur la fiche produit
    | ("délai estimé", 7.2.2). Le CDC ne fournit pas de méthode de calcul —
    | aucune donnée logistique réelle (délais par étape/transporteur)
    | n'existe avant le Sprint 8. Valeur indicative statique, à remplacer
    | par une estimation calculée à partir des délais SLA réels dès qu'ils
    | existeront.
    |
    */

    'delivery_estimate_days_min' => 15,

    'delivery_estimate_days_max' => 25,

    'default_per_page' => 20,

    'max_per_page' => 50,

];
