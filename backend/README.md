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

> **Note** : `Boutique::canBeDeleted()` retourne toujours `true` pour l'instant — la règle « pas de suppression si commande active » ne peut pas encore être appliquée, le modèle Commande arrivant au Sprint 6.

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

## Tests

```bash
php artisan test
vendor/bin/pint --test
```
