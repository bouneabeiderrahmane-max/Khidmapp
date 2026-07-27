# Khidmapp — Backend (Laravel)

API REST du projet Khidmapp. Voir [`../docs/PLAN.md`](../docs/PLAN.md) pour l'architecture complète, le modèle de données et le découpage en sprints.

## Prérequis

- PHP 8.4, Composer
- PostgreSQL 16, Redis 7 (voir `../docker-compose.yml`)

## Démarrer

```bash
cp .env.example .env
composer install
php artisan key:generate
php artisan jwt:secret
php artisan migrate
php artisan db:seed   # rôles/permissions (client, service_client, administrateur)
php artisan serve
```

## Documentation API

Générée automatiquement depuis le code (`dedoc/scramble`) :

- Swagger UI : `GET /docs/api`
- Spec OpenAPI JSON : `GET /docs/api.json`

## Authentification (Sprint 1)

- `POST /api/v1/auth/otp/request` — envoie un code à 6 chiffres par SMS (voir note ci-dessous)
- `POST /api/v1/auth/otp/verify` — vérifie le code, crée le compte au premier essai ou connecte l'utilisateur existant
- `POST /api/v1/auth/register` — inscription e-mail + mot de passe
- `POST /api/v1/auth/login` — connexion e-mail + mot de passe
- `POST /api/v1/auth/refresh` — renouvelle le token JWT (authentifié)
- `POST /api/v1/auth/logout` — invalide le token courant (authentifié)
- `GET/PUT/DELETE /api/v1/me` — profil (consultation, modification, désactivation du compte)
- `GET/POST/PUT/DELETE /api/v1/addresses` — adresses de livraison multiples

> **Note SMS** : le cahier des charges ne précise pas d'opérateur SMS pour la Mauritanie. En attendant ce choix, l'envoi du code OTP passe par `App\Services\Sms\LogSmsGateway`, qui écrit le message dans les logs applicatifs au lieu de l'envoyer réellement — ce n'est **pas** un canal de production. Remplacer l'implémentation liée à `App\Contracts\SmsGateway` (binding dans `AppServiceProvider`) une fois un opérateur choisi.

## Boutiques (Sprint 2)

- `GET /api/v1/boutiques` / `GET /api/v1/boutiques/{slug}` — catalogue public, boutiques actives uniquement
- `GET/POST/PUT/DELETE /api/v1/admin/boutiques` + `PATCH /api/v1/admin/boutiques/{id}/status` — gestion complète (permission `boutiques.manage`, rôle `administrateur` uniquement)

## Synchronisation catalogue (Sprint 3)

- `POST /api/v1/admin/boutiques/{id}/sync` — déclenche une synchronisation immédiate (mise en file d'attente Redis)
- `GET /api/v1/admin/boutiques/{id}/sync-logs` — journal de synchronisation (8.2.3)
- `GET/POST/PUT/DELETE /api/v1/admin/categories`, `GET /api/v1/categories` (public)
- `GET/PUT /api/v1/admin/boutiques/{id}/category-mappings[/{id}]` — mapping catégorie source → catégorie unifiée
- `GET/PUT /api/v1/admin/products[/{id}]` — correction manuelle des traductions (verrouille le champ contre les prochaines synchros)
- `php artisan catalog:sync {boutique?}` — déclenche en CLI ; un planificateur horaire synchronise automatiquement chaque boutique selon sa fréquence configurée (`sync_config.frequency_hours`)

> **Notes** :
> - `App\Services\Catalog\StubCatalogFetcher` **n'est pas un scraper réel** — le cahier des charges confirme qu'aucune boutique ne fournit d'API officielle et que la récupération devra respecter les CGU de chaque site ; ce travail (un connecteur par boutique) reste à faire. Le placeholder génère des produits fictifs pour que le moteur de synchro soit développé et testé.
> - `App\Services\Catalog\PassthroughTranslator` **ne traduit rien** (recopie le texte source) — aucun service de traduction n'est précisé par le cahier des charges. La correction manuelle via `PUT /api/v1/admin/products/{id}` est pleinement fonctionnelle et prioritaire sur la traduction automatique.

## Moteur de calcul de prix (Sprint 4)

- `GET/POST /api/v1/admin/exchange-rates` — historique des taux (ajout uniquement, jamais de modification rétroactive)
- `GET/POST /api/v1/admin/margin-rules` — règles de marge (global/boutique/catégorie), précédence catégorie > boutique > global
- `GET/POST/PUT/DELETE /api/v1/admin/delivery-fee-tiers` — grille de frais de livraison par tranche de prix et par zone
- `GET /api/v1/admin/products/{id}/price-preview?variant_id=X&zone=Y` — aperçu du prix final calculé (permission `pricing.manage_margin`)

Le cas chiffré du CDC (8.3.1 : 29,95 € → 2 057,16 MRU) est vérifié au centime près dans `PricingServiceTest`.

## Catalogue & recherche (Sprint 5)

- `GET /api/v1/products` — recherche publique : `q` (mot-clé), `boutique`/`category` (slug), `color`, `size`, `min_price`/`max_price` (MRU), `sort` (`newest`/`price_asc`/`price_desc`), pagination
- `GET /api/v1/products/{id}` — fiche produit : images, nom/description bilingues, boutique d'origine, prix final MRU par variante, délai de livraison estimé

Aucun prix EUR, URL ou référence de boutique source n'est jamais exposé sur ces routes.

> **Notes** :
> - Le filtrage par prix et la recherche par mot-clé (champ JSON bilingue) sont appliqués en mémoire après un premier filtrage SQL, pas entièrement en base — voir le docblock de `App\Services\Catalog\ProductSearchService`. Suffisant pour cette session, à revoir si le catalogue grossit significativement.
> - Le délai de livraison affiché (`config('catalog.delivery_estimate_days_*')`) est une valeur statique, faute de données logistiques réelles avant le Sprint 8 — ce n'est **pas** une estimation calculée.

## Panier & commande (Sprint 6)

- `GET/POST/PUT/DELETE /api/v1/cart[/items/{id}]` — panier du client connecté (fusionne la quantité si la variante y est déjà, refuse un produit/boutique inactif)
- `POST /api/v1/orders` — passage de commande depuis le panier (adresse + mode de paiement), historise taux de change/marge/frais par ligne, vide le panier
- `GET /api/v1/orders`, `GET /api/v1/orders/{id}` — commandes du client connecté uniquement
- `POST /api/v1/orders/{id}/cancel` — auto-annulation, réservée à la fenêtre gratuite (avant « Expédié par la boutique »)
- `GET /api/v1/admin/orders` (filtrable par `status`/`user_id`/`boutique_id`), `GET .../{id}`, `PATCH .../{id}/status` — supervision globale et intervention manuelle sur le statut (permission `orders.manage_status`)
- `POST /api/v1/admin/orders` — commande manuelle pour le compte d'un client, assistance téléphonique/vente assistée (permission `orders.create_for_client`), trace l'agent via `created_by_agent_id`

> **Notes** :
> - « Brouillon » (premier statut du CDC 8.4) n'est jamais atteint par le parcours normal : le panier joue déjà ce rôle avant le passage de commande, qui démarre directement à `paiement_en_attente`. Voir `App\Support\OrderStatus` et `docs/PLAN.md` §7septies.
> - Les frais de livraison sont calculés **une seule fois** sur le sous-total agrégé du panier/de la commande (`App\Services\Pricing\CartPricingCalculator`), jamais sommés ligne par ligne (8.6).
> - Une annulation au-delà de la fenêtre gratuite est marquée `cancellation_fee_applicable = true` mais **aucun montant n'est réellement prélevé**, faute de mécanisme de prélèvement (voir Sprint 7 ci-dessous) ; la commande est simplement signalée pour traitement manuel par le service client.

## Paiements (Sprint 7)

- `POST /api/v1/orders/{id}/payments/bankily/initiate` — initie un paiement Bankily (idempotent : réutilise une transaction déjà `pending`)
- `POST /api/v1/orders/{id}/payment-proof` — soumet une preuve de paiement manuel (image, 5 Mo max)
- `POST /api/v1/webhooks/bankily` — point d'entrée public pour la confirmation Bankily (en-tête `X-Bankily-Signature`), transitionne automatiquement la commande vers « Paiement validé »
- `GET /api/v1/admin/payments/manual` (filtrable par `status`), `GET .../{id}/proof` (téléchargement de la preuve), `POST .../{id}/validate`, `POST .../{id}/reject` (motif obligatoire), `POST .../{id}/request-info` (note obligatoire) — revue des paiements manuels (permission `payments.validate_manual`)

> **Notes** :
> - `App\Services\Payment\StubBankilyGateway` **n'est pas une intégration Bankily fonctionnelle** — le cahier des charges ne fournit ni identifiants, ni format d'API, ni schéma de signature de webhook réel. Le placeholder génère une référence locale sans appel réseau ; la protection du webhook est un simple secret partagé de développement (`BANKILY_WEBHOOK_SECRET`). À remplacer dès l'obtention d'un accès marchand Bankily réel (le contrat `App\Contracts\BankilyGateway` est prêt).
> - Un client ayant une preuve de paiement manuelle `pending` sur une commande ne peut pas en passer une nouvelle nécessitant un paiement (8.5.3) — commutateur `config('payments.block_new_orders_with_pending_manual_proof')`, pas encore exposé dans une UI de réglages admin (aucun module de ce type n'existe).
> - Chaque soumission de preuve manuelle crée un nouvel enregistrement `Payment` plutôt que de réécrire le précédent : l'historique complet (refus, demandes de complément, re-soumissions) reste consultable.

## Tests

```bash
php artisan test
vendor/bin/pint --test
```
