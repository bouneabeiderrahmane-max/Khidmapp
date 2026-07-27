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

];
