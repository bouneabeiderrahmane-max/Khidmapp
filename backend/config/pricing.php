<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Moteur de calcul de prix (CDC 8.3)
    |--------------------------------------------------------------------------
    */

    'default_margin_percent' => (float) env('DEFAULT_MARGIN_PERCENT', 20),

    'default_currency_pair' => env('DEFAULT_CURRENCY_PAIR', 'EUR_MRU'),

    'default_zone' => 'nouakchott',

    /*
    |--------------------------------------------------------------------------
    | Coût de gestion (visible client)
    |--------------------------------------------------------------------------
    |
    | Contrairement à la marge (PriceMarginTier), ce pourcentage est
    | explicitement affiché au client sur son panier/commande ("Coût de
    | gestion"), au même titre que le sous-total et la livraison — calculé
    | sur (sous-total article + frais de livraison), pas sur le sous-total
    | seul (vérifié contre l'exemple chiffré fourni par l'utilisateur).
    |
    */

    'management_fee_percent' => (float) env('MANAGEMENT_FEE_PERCENT', 5),

];
