# Khidmapp — Plan de développement détaillé (v0.2)

> Statut : aligné sur le « Cahier des charges fonctionnel et technique v1.0 » (26 juillet 2026). Les points encore ouverts sont marqués **[À VALIDER]** ; tout le reste reflète des règles confirmées par le cahier des charges.

---

## 0. Repo et organisation générale

Un seul dépôt GitHub est en scope (`bouneabeiderrahmane-max/khidmapp`) : monorepo.

```
khidmapp/
├── backend/          # API Laravel (REST, PostgreSQL, Redis, JWT)
├── mobile/           # App Flutter (Android + iOS)
├── admin/            # Interface d'administration web (React)
├── docs/             # Documentation technique & fonctionnelle
├── docker-compose.yml
└── README.md
```

Chaque module (section 6) est développé dans son propre commit/série de commits : migrations, modèles, services/contrôleurs, routes API, tests, mise à jour OpenAPI, note de synthèse.

---

## 1. Choix techniques

| Sujet | Choix | Justification |
|---|---|---|
| Backend | Laravel 13 (PHP 8.4) | Version stable installée par `composer create-project laravel/laravel` au moment du démarrage du projet |
| Auth JWT | `php-open-source-saver/jwt-auth` | Fork activement maintenu de `tymon/jwt-auth` (abandonné) ; access + refresh token |
| Autorisation (rôles/permissions) | `spatie/laravel-permission` | Confirmé — RBAC piloté par base de données, granularité par module (lecture/écriture/validation, cf. 8.9.6 du CDC) |
| i18n backend | `lang/{fr,ar}` (Laravel-Lang officiel pour les messages système : validation, auth, pagination) + colonnes JSON `{ "fr": "...", "ar": "..." }` pour le contenu dynamique traduisible (produits, catégories, statuts) | Aucun texte en dur, conforme à la section 8.10 du CDC |
| RTL / i18n mobile | `flutter_localizations` + fichiers ARB (`flutter gen-l10n`) ; RTL entièrement natif via Flutter (Directionality résolue automatiquement par la locale) | Confirmé par test visuel (bascule fr/ar) |
| Admin web | React 19 + Vite + TypeScript + Tailwind v4 + TanStack Query + react-router-dom + react-i18next | RTL vérifié visuellement (bascule `dir` + police + alignement sidebar) |
| Base de données | PostgreSQL 16 | Imposé — migrations validées sur instance réelle |
| Cache / queues | Redis (cache + sessions + queues) | Imposé |
| Stockage fichiers | S3 compatible (MinIO en local/dev via docker-compose) | Imposé |
| Notifications push | Firebase Cloud Messaging | Imposé — intégration prévue en Sprint 9 |
| Paiement auto | API Bankily | Imposé — intégration prévue en Sprint 7 |
| Documentation API | `dedoc/scramble` (génère l'OpenAPI depuis le code, sert Swagger UI sur `/docs/api`) | Zéro annotation manuelle à maintenir |

---

## 2. Modèle de données (v0.2)

### Identité & autorisation
- **users** : id, name, phone (unique, nullable), phone_verified_at, email (unique, nullable), email_verified_at, password (nullable — inscription possible par téléphone+OTP sans mot de passe initial), locale (fr/ar, défaut fr), timestamps, soft deletes
- **otp_codes** : id, phone, code_hash, expires_at, consumed_at
- **addresses** : id, user_id, label, city, area, geo_lat/lng, phone, is_default, timestamps
- **roles**, **permissions**, **role_has_permissions**, **model_has_permissions**, **model_has_roles** (tables `spatie/laravel-permission`)

Rôles internes confirmés par le CDC (section 7.1) : **Client**, **Service client**, **Administrateur** — pas de rôle `super_admin` distinct dans le cahier des charges.

### Catalogue
- **boutiques** : id, name, slug, logo_url, banner_url, base_url, country_code, currency_code, status (`active`/`inactive`/`en_pause`/`en_test` — 8.1), default_margin_percent (nullable, surcharge du défaut global), sync_config (jsonb), soft deletes
- **categories** : id, name (jsonb fr/ar), parent_id, slug
- **boutique_categories** : mapping catégorie source → catégorie unifiée Khidmapp (8.2.1)
- **products** : id, boutique_id, external_ref, category_id, name (jsonb), description (jsonb), images (jsonb), base_price_eur, status (`active`/`indisponible`/`discontinued`), last_synced_at, soft deletes
- **product_variants** : id, product_id, sku, size, color, price_eur, stock_status, external_variant_ref

### Tarification
- **exchange_rates** : id, currency_pair (EUR_MRU), rate, effective_at, created_by
- **margin_rules** : id, scope_type (`global`/`boutique`/`category`), scope_id (nullable pour global), percent, effective_at, created_by
  - Précédence confirmée (8.3.2) : une marge boutique ou catégorie prévaut sur le défaut global. **[À VALIDER]** : en cas de conflit boutique *et* catégorie simultanément sur un même produit, le CDC ne tranche pas explicitement — je retiens par défaut la règle « la règle la plus spécifique gagne » (catégorie > boutique > global), à confirmer.
- **delivery_fee_tiers** : id, min_weight_kg, max_weight_kg, min_price_mru, max_price_mru, delivery_zone, fee_mru (grille par poids/volume **ou** palier de prix, différenciable par zone — 8.9.4)

### Commande
- **carts**, **cart_items** (variant_id, qty, prix simulé)
- **orders** : id, user_id, status, address_id, created_by_agent_id (nullable — commande manuelle, 8.4.2), subtotal_eur, exchange_rate_snapshot, margin_percent_snapshot, delivery_fee_snapshot_mru, total_mru, payment_method, timestamps
- **order_items** : id, order_id, product_variant_id, qty, unit_price_eur, unit_price_mru_snapshot
- **order_status_history** : id, order_id, from_status, to_status, actor_type (`system`/`client`/`service_client`/`administrateur`), actor_id, note, created_at

### Paiement
- **payments** : id, order_id, method (`bankily`/`manual`), status (`pending`/`validated`/`rejected`/`info_requested`), proof_file_url, external_ref (référence transaction Bankily), rejection_reason, validated_by, validated_at, amount_mru

### Logistique
- **logistics_events** : id, order_id, step (1 à 10, cf. table 8.6), started_at, completed_at, expected_deadline_at, actor, notes — alimente les alertes de dépassement de délai (8.6.2) et le tableau de bord logistique

### Notifications & support
- **notifications** : id, user_id, channel (`push`/`sms`/`email`), locale, template_key, payload (jsonb), status, sent_at
- **notification_preferences** : user_id, channel, enabled (avec canaux critiques non désactivables, 8.7.2)
- **complaints** : id, order_id, user_id, category (produit non conforme/retard/dommage/erreur facturation/autre), status (`ouverte`/`en_cours`/`en_attente_client`/`resolue`/`cloturee`), priority
- **complaint_messages** : complaint_id, sender_type, sender_id, message, attachments (jsonb)

### Synchronisation & audit
- **sync_logs** : id, boutique_id, started_at, finished_at, status, products_created/updated/disabled, errors (jsonb)
- **audit_logs** : id, actor_type, actor_id, action, subject_type, subject_id, before (jsonb), after (jsonb), created_at — couvre statuts, paiements, marges, rôles (exigence sécurité)

### Promotions (8.9.5)
- **promotions** : id, code, type (`percent`/`fixed`), value, max_discount_mru (nullable), valid_from, valid_until, max_uses, min_order_amount_mru, boutique_id (nullable = toutes boutiques)

Index prévus : `orders.status`, `orders.user_id`, `products.boutique_id`, `products.category_id`, `order_status_history.order_id`, `payments.status`.

---

## 3. Cycle de statuts de commande (confirmé — CDC 8.4)

| # | Statut | Déclencheur | Annulation |
|---|---|---|---|
| 1 | Brouillon | Client ajoute au panier | Libre |
| 2 | Paiement en attente | Client valide la commande | Libre |
| 3 | Paiement validé | Confirmation Bankily ou service client | Libre |
| 4 | Achat en cours | Déclenchement interne post-paiement | Libre |
| 5 | Commandé auprès de la boutique | Confirmation d'achat obtenue | Libre |
| 6 | Expédié par la boutique | Expédition confirmée par la boutique | **Frais possibles au-delà de ce statut** |
| 7 | Reçu à Madrid | Scan de réception 3PL | Avec frais / après retour |
| 8 | Contrôle qualité | Début du contrôle 3PL | Avec frais / après retour |
| 9 | Consolidation | Constitution du lot de transport | Avec frais / après retour |
| 10 | Expédié vers Nouakchott | Prise en charge transporteur | Avec frais / après retour |
| 11 | Arrivé à Nouakchott | Réception locale confirmée | Avec frais / après retour |
| 12 | En cours de livraison | Attribution livreur | Avec frais / après retour |
| 13 | Livré | Confirmation de livraison | Terminal |
| 14 | Annulé | Demande client ou décision Khidmapp | Terminal |
| 15 | Remboursé | Validation service client/admin, motif obligatoire | Terminal |

Règles transverses : chaque transition est horodatée + attribuée à un acteur (traçabilité complète) ; certaines transitions déclenchent une notification (section 8.7) ; le passage à « Remboursé » exige une validation explicite avec motif.

---

## 4. Matrice de permissions (confirmée — CDC 7.5, 8.9.6)

Rôles : **Client**, **Service client**, **Administrateur** — piloté par `spatie/laravel-permission`, granularité par module (lecture/écriture/validation), jamais de rôle en dur dans les contrôleurs.

| Action | Client | Service client | Administrateur |
|---|---|---|---|
| Consulter le catalogue | Oui | Oui | Oui |
| Passer une commande | Oui | Pour le compte d'un client | Pour le compte d'un client |
| Valider un paiement manuel | Non | Oui | Oui |
| Modifier une marge | Non | Non | Oui |
| Créer une boutique | Non | Non | Oui |
| Gérer les rôles internes | Non | Non | Oui |
| Tableau de bord global | Non | Consultation limitée | Oui |

---

## 5. Moteur de calcul de prix (confirmé — CDC 8.3)

```
prix_boutique_eur
  → conversion EUR → MRU (taux en vigueur, table exchange_rates)
  → majoration commerciale (20 % par défaut ; surcharge boutique ou catégorie prioritaire — [À VALIDER] : précédence si les deux sont définies simultanément)
  → ajout frais de livraison (grille poids/volume/palier de prix, éventuellement par zone)
  → prix final MRU affiché au client
```

Chaque commande fige `exchange_rate_snapshot`, `margin_percent_snapshot`, `delivery_fee_snapshot_mru` au moment de l'achat, indépendamment de l'évolution ultérieure des paramètres globaux.

Exemple de référence (8.3.1) : 29,95 € × 47,50 MRU/€ = 1 422,63 MRU + 20 % (284,53) + 350 MRU de livraison = **2 057,16 MRU**. Sert de cas de test pour le moteur de prix (Sprint 4).

---

## 6. Découpage en sprints

| Sprint | Contenu | Statut |
|---|---|---|
| 0 | Socle technique | **Fait** (voir §7) |
| 1 | Authentification & comptes | **Fait** (voir §7bis) |
| 2 | Boutiques | **Fait** (voir §7ter) |
| 3 | Synchronisation catalogue | À venir |
| 4 | Moteur de calcul de prix | À venir |
| 5 | Catalogue & recherche | À venir |
| 6 | Panier & commande (state machine 15 statuts) | À venir |
| 7 | Paiements (Bankily + manuel) | À venir |
| 8 | Logistique (10 étapes, alertes SLA) | À venir |
| 9 | Notifications (FCM/SMS/e-mail) | À venir |
| 10 | Support & réclamations | À venir |
| 11 | Administration & pilotage (dashboard, exports) | À venir |

---

## 7. Sprint 0 — ce qui a été livré

**Backend** (`backend/`) : Laravel 13, PostgreSQL + Redis configurés et testés sur instances réelles, migrations `users`/`permissions` appliquées avec succès, JWT (`php-open-source-saver/jwt-auth`) câblé sur le guard `api`, RBAC `spatie/laravel-permission` (trait `HasRoles` sur `User`), i18n FR/AR officiel (Laravel-Lang) avec middleware `SetLocaleFromRequest` (résolution via `Accept-Language`, testée avec succès), route `/api/v1/health`, documentation OpenAPI auto-générée (`dedoc/scramble`, `/docs/api`), 4 tests PHPUnit verts, style Pint conforme.

**Admin** (`admin/`) : React 19 + Vite + TypeScript + Tailwind v4 + TanStack Query + react-router-dom + react-i18next. Layout avec sélecteur de langue, bascule RTL complète vérifiée visuellement (capture d'écran fr/ar). Build de production OK.

**Mobile** (`mobile/`) : Flutter 3.44 (SDK installé et testé dans cet environnement). Structure `core/`, `features/`, `l10n/`, `routing/`. Localisation ARB fr/ar avec `flutter gen-l10n`, RTL natif Flutter. `flutter analyze` : 0 problème. `flutter test` : test de fumée vert.

**Infra** : `docker-compose.yml` (Postgres 16, Redis 7, MinIO + init du bucket) pour le développement local — non testé dans cet environnement (pas de démon Docker disponible ici), mais suit un schéma standard éprouvé.

**CI** : trois workflows GitHub Actions (`backend.yml`, `admin.yml`, `mobile.yml`), déclenchés par dossier modifié, reproduisant exactement les commandes validées manuellement ci-dessus (migrations Postgres réelles, tests, lint/analyze, build).

---

## 7bis. Sprint 1 — ce qui a été livré

**Comptes & authentification** : inscription/connexion par téléphone + OTP (code à 6 chiffres, expiration 5 min, verrouillage après 5 essais, invalidation de l'ancien code à chaque nouvelle demande), inscription/connexion par e-mail + mot de passe, JWT access token + refresh (`php-open-source-saver/jwt-auth`), déconnexion (invalidation du token), attribution automatique du rôle `client` à l'inscription (les rôles internes `service_client`/`administrateur` ne sont pas auto-attribuables — réservés à une gestion admin future).

**Profil & adresses** : `GET/PUT/DELETE /api/v1/me` (consultation, modification nom/téléphone/e-mail/langue, désactivation de compte via soft delete + déconnexion), CRUD complet `/api/v1/addresses` avec règle « une seule adresse par défaut » (la première créée l'est automatiquement ; en définir une nouvelle par défaut désactive l'ancienne) et vérification de propriété (403 si un utilisateur tente de modifier l'adresse d'un autre).

**RBAC** : seeder `RolePermissionSeeder` créant les 3 rôles confirmés par le CDC et la matrice de permissions de la section 4, via des constantes (`App\Support\Roles`, `App\Support\Permissions`) plutôt que des chaînes en dur.

**Tests** : 27 tests (Feature + Unit) verts couvrant OTP (génération, vérification, expiration, anti-rejeu, verrouillage), auth e-mail, cycle de vie du token (refresh/logout/route protégée), profil et adresses (y compris l'isolation entre utilisateurs). Style Pint conforme. Migrations validées sur PostgreSQL réel.

**Traductions** : nouveau fichier applicatif `lang/{fr,ar}/khidmapp.php` pour les messages métier (OTP envoyé/invalide, échec de connexion, non-authentifié, etc.) — distinct des fichiers `validation`/`auth` officiels de Laravel-Lang.

**Point signalé explicitement** : l'envoi réel du SMS n'est **pas implémenté** — le cahier des charges ne précise pas d'opérateur pour la Mauritanie. `App\Services\Sms\LogSmsGateway` (derrière l'interface `App\Contracts\SmsGateway`) se contente d'écrire le code dans les logs. À remplacer par une vraie intégration dès qu'un opérateur SMS est choisi.

---

## 7ter. Sprint 2 — ce qui a été livré

**Boutiques** : CRUD complet réservé à l'administrateur (`GET/POST/PUT/DELETE /api/v1/admin/boutiques`, permission `boutiques.manage`), avec transition de statut dédiée (`PATCH .../status`) parmi les 4 statuts confirmés (Active/Inactive/En pause/En test — 8.1). Champs conformes à la section 8.1 : nom, logo/bannière (URL pour l'instant), URL source, pays, devise, statut, marge spécifique, paramètres de synchronisation (`sync_config` JSON : fréquence, catégories incluses/exclues, traduction automatique, seuil d'alerte).

**Catalogue public** : `GET /api/v1/boutiques` et `GET /api/v1/boutiques/{slug}` ne montrent que les boutiques au statut `active` (règle 8.1.2), et masquent la marge et la configuration de synchro (réservées à l'admin) — vérifié par test.

**Boutiques de référence** : les 8 boutiques de lancement (8.1.1) sont seedées au statut `en_test`, pays ES / devise EUR.

**Règles de gestion** : slug auto-généré et stable (les mises à jour de nom ne cassent pas l'URL publique) ; suppression = soft delete. La règle « suppression impossible s'il existe une commande active » (8.1.2) est représentée par `Boutique::canBeDeleted()` mais **retourne toujours `true` pour l'instant** — le modèle Commande n'existe pas avant le Sprint 6. Point à compléter avant mise en production, signalé explicitement dans le code et ici.

**Tests** : 13 tests Feature (visibilité publique, permissions par rôle — y compris que `service_client` ne peut pas gérer les boutiques —, CRUD, transition de statut, validation) ; 40 tests au total sur l'ensemble du backend, tous verts. Un bug a été trouvé et corrigé pendant le développement : après `create()`, l'instance en mémoire ne reflétait pas le défaut `en_test` appliqué côté PostgreSQL (`status` apparaissait `null` dans la réponse JSON alors que la ligne en base était correcte) — corrigé par un `->fresh()` après création.

---

## 8. Points encore ouverts

1. Précédence exacte marge boutique vs marge catégorie en cas de définition simultanée sur un même produit (règle par défaut retenue : catégorie > boutique > global) — Sprint 4.
2. Détail fin des permissions par sous-action au sein de chaque module (la matrice CDC 7.5 est une synthèse ; la granularité complète sera affinée module par module au fil des sprints, avec validation à chaque fois).
3. **Opérateur SMS pour l'envoi réel des OTP** : non précisé par le cahier des charges — à trancher avant mise en production (voir §7bis).
4. **`Boutique::canBeDeleted()` à compléter** dès que le modèle Commande existe (Sprint 6) — pour l'instant la suppression n'est jamais bloquée par une commande active, faute de commandes (voir §7ter).
5. Upload réel de logo/bannière boutique (actuellement de simples URL) — à raccorder au stockage S3/MinIO si un flux d'upload dédié est souhaité plutôt que de simples liens externes.
