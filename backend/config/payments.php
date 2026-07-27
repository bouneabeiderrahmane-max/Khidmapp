<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Paiements (CDC 8.5)
    |--------------------------------------------------------------------------
    |
    | block_new_orders_with_pending_manual_proof (8.5.3) : "Un client ne
    | peut passer une nouvelle commande nécessitant un paiement tant qu'une
    | preuve de paiement en attente n'a pas été traitée, sauf configuration
    | contraire de l'administrateur." Il n'existe pas encore de module de
    | réglages globaux modifiable depuis l'administration (aucun mécanisme
    | de ce type n'a été construit dans les sprints précédents) : ce
    | commutateur est donc pour l'instant une valeur de configuration
    | statique, pas un réglage exposé dans l'UI admin — voir docs/PLAN.md §8.
    |
    */

    'block_new_orders_with_pending_manual_proof' => env('PAYMENTS_BLOCK_NEW_ORDERS_WITH_PENDING_PROOF', true),

    /*
    |--------------------------------------------------------------------------
    | Bankily (CDC 8.5.1)
    |--------------------------------------------------------------------------
    |
    | Le cahier des charges ne fournit ni les identifiants, ni le format de
    | requête/réponse, ni le schéma de signature du webhook de l'API
    | Bankily réelle. `App\Services\Payment\StubBankilyGateway` simule donc
    | l'initiation d'un paiement (référence générée localement, aucun appel
    | réseau) — ce n'est PAS une intégration Bankily fonctionnelle. Le
    | secret ci-dessous protège le point d'entrée webhook par un simple
    | partage de secret (en-tête `X-Bankily-Signature`) en attendant le vrai
    | schéma de signature Bankily.
    |
    */

    'bankily' => [
        // base_url/api_key/merchant_id ne sont consommés par aucun code pour
        // l'instant (StubBankilyGateway n'effectue aucun appel réseau) —
        // réservés à la vraie implémentation qui remplacera le placeholder.
        'base_url' => env('BANKILY_BASE_URL'),
        'api_key' => env('BANKILY_API_KEY'),
        'merchant_id' => env('BANKILY_MERCHANT_ID'),
        'webhook_secret' => env('BANKILY_WEBHOOK_SECRET', 'khidmapp-dev-bankily-secret'),
    ],

    'proof_max_size_kb' => 5120,

];
