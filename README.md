# Khidmapp

Plateforme mobile et web de shopping international : les clients basés en Mauritanie achètent auprès de boutiques espagnoles via une application unique. Khidmapp gère la commande, le paiement, l'achat, la réception en entrepôt 3PL (Madrid), le contrôle qualité, la consolidation, le transport international et la livraison finale à Nouakchott.

## Structure du dépôt

```
khidmapp/
├── backend/   # API Laravel (REST, PostgreSQL, Redis, JWT) — voir backend/README.md
├── mobile/    # Application Flutter (Android + iOS) — voir mobile/README.md
├── admin/     # Interface d'administration web (React)
├── docs/      # Documentation technique & fonctionnelle
├── docker-compose.yml   # Postgres + Redis + MinIO pour le développement local
```

## Démarrage rapide

```bash
# Infra locale (Postgres, Redis, MinIO)
docker compose up -d

# Backend
cd backend && cp .env.example .env && composer install
php artisan key:generate && php artisan jwt:secret && php artisan migrate && php artisan db:seed
php artisan serve

# Admin
cd admin && npm install && npm run dev

# Mobile
cd mobile && flutter pub get && flutter gen-l10n && flutter run
```

## Documentation

- [`docs/PLAN.md`](docs/PLAN.md) — plan de développement détaillé : architecture, schéma de données, cycle de statuts de commande, matrice de permissions, découpage en sprints.

## Statut du projet

- **Sprint 0** (socle technique) : terminé — backend Laravel (JWT, RBAC, i18n FR/AR, OpenAPI), admin React (i18n/RTL vérifié), mobile Flutter (i18n/RTL, routing), Docker Compose, CI GitHub Actions pour les trois applications.
- **Sprint 1** (authentification & comptes) : terminé — OTP téléphone, e-mail/mot de passe, JWT + refresh, profil, adresses multiples, rôles (client/service_client/administrateur).
- **Sprint 2** (boutiques) : terminé — CRUD admin, statuts (Active/Inactive/En pause/En test), catalogue public filtré, 8 boutiques de référence seedées. 40 tests verts au total.

Voir `docs/PLAN.md` section 7/7bis/7ter pour le détail de chaque sprint livré, et section 8 pour les points encore ouverts (précédence des marges, granularité fine des permissions, opérateur SMS à choisir, règle de suppression boutique à finaliser au Sprint 6) avant le Sprint 3.
