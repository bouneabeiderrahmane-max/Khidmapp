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

## Tests

```bash
php artisan test
vendor/bin/pint --test
```
