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
| 3 | Synchronisation catalogue | **Fait** (voir §7quater) |
| 4 | Moteur de calcul de prix | **Fait** (voir §7quinquies) |
| 5 | Catalogue & recherche | **Fait** (voir §7sexies) |
| 6 | Panier & commande (state machine 15 statuts) | **Fait** (voir §7septies) |
| 7 | Paiements (Bankily + manuel) | **Fait** (voir §7octies) |
| 8 | Logistique (10 étapes, alertes SLA) | **Fait** (voir §7nonies) |
| 9 | Notifications (FCM/SMS/e-mail) | **Fait** (voir §7decies) |
| 10 | Support & réclamations | **Fait** (voir §7undecies) |
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

## 7quater. Sprint 3 — ce qui a été livré

**Modèle de données** : `categories` (nom bilingue jsonb, hiérarchie parent/enfant), `category_mappings` (catégorie source par boutique → catégorie unifiée, créée automatiquement au premier produit rencontré dans cette catégorie source), `products` (nom/description bilingues, images, prix EUR source, statut, `unavailable_since`, `translation_locked`), `product_variants` (taille, couleur, prix, disponibilité), `sync_logs` (compteurs créés/mis à jour/désactivés + erreurs).

**Moteur de synchronisation** (`CatalogSyncService`) : upsert produits/variantes par référence externe, création automatique des mappings catégorie source manquants, détection d'anomalies (8.2.2) — un produit non revu lors d'un cycle est marqué « indisponible » puis « retiré du catalogue » après un délai configurable par boutique (`sync_config.unavailable_grace_days`, 14 jours par défaut) — et préservation intégrale du dernier catalogue valide en cas d'échec (exception capturée, journalisée dans `sync_logs`, aucun produit existant modifié).

**Correction manuelle des traductions (8.2.1)** : un bug a été détecté et corrigé pendant le développement — une resynchronisation écrasait silencieusement toute correction manuelle du nom/de la description d'un produit, ce qui aurait rendu la fonctionnalité inutile. Un champ `translation_locked` (posé automatiquement par `PUT /api/v1/admin/products/{id}` dès qu'on modifie `name` ou `description`) fait que `CatalogSyncService` ne touche plus jamais à ces champs pour un produit corrigé, quel que soit le nombre de synchronisations suivantes — vérifié par test.

**Déclenchement** : commande `php artisan catalog:sync {boutique?}`, job Redis (`SyncBoutiqueCatalog`, `QUEUE_CONNECTION=redis`), planificateur horaire qui ne synchronise que les boutiques dont la fréquence configurée (`sync_config.frequency_hours`) est dépassée depuis leur dernière synchronisation. Les boutiques « en_test » sont synchronisées au même titre que les « active » (c'est tout l'intérêt de ce statut — valider la synchro avant publication, 8.1.2).

**Endpoints admin** (permission `catalog.manage`, nouvelle — extension du RBAC, absente de la matrice de synthèse 7.5 mais cohérente avec la granularité par module demandée en 8.9.6) : CRUD catégories, consultation/mapping des catégories source par boutique, déclenchement manuel de synchro et consultation du journal (8.2.3), liste/consultation/correction manuelle des produits.

**Points signalés explicitement — non finalisés** :
- **`StubCatalogFetcher` n'est pas un scraper réel.** Le cahier des charges (3.3) confirme qu'aucune boutique ne propose d'API officielle et que la récupération devra s'appuyer sur une lecture structurée des pages publiques de chaque site, dans le respect de leurs CGU. Écrire un vrai connecteur par boutique (Zara, Mango, Bershka, ...) — structure HTML propre à chaque site, mesures anti-bot, vérification des CGU — est un chantier à part entière qui n'a pas été fait dans cette session. `StubCatalogFetcher` génère 3 produits fictifs déterministes par boutique, uniquement pour permettre au moteur de synchronisation d'être développé et testé de bout en bout. À remplacer avant toute mise en production, boutique par boutique (l'interface `CatalogFetcher` est prête à recevoir une vraie implémentation).
- **`PassthroughTranslator` ne traduit rien** (recopie le texte source dans les deux langues). Le CDC demande une traduction automatique FR/AR mais ne précise aucun service (Google Cloud Translation, DeepL, etc.). La correction manuelle, elle, est pleinement fonctionnelle.

**Tests** : 21 nouveaux tests (Unit sur le moteur de synchro : création, mise à jour, anomalies, délai de grâce, verrou de traduction, échec préservant le catalogue ; Feature sur catégories, mapping, déclenchement/journal de synchro, produits admin) — 61 tests au total, tous verts. Migrations validées sur PostgreSQL réel.

---

## 7quinquies. Sprint 4 — ce qui a été livré

**Précédence des marges tranchée** : catégorie > boutique > global, confirmée par toi avant le développement (l'ambiguïté notée depuis le Sprint 2 est résolue). **Frais de livraison** : grille par tranche de prix final (`delivery_fee_tiers` : zone, palier min/max, frais), différenciée par zone — choix confirmé plutôt que d'ajouter poids/volume aux produits maintenant.

**Modèle de données** : `exchange_rates` (paire de devises, taux, date d'entrée en vigueur, auteur — historique complet, jamais écrasé), `margin_rules` (portée global/boutique/catégorie, pourcentage, date d'entrée en vigueur), `delivery_fee_tiers` (zone, palier de prix, frais). Valeurs de départ seedées d'après l'exemple chiffré du CDC : 1 € = 47,50 MRU, marge globale 20 %, livraison forfaitaire 350 MRU vers Nouakchott.

**`PricingService`** : conversion → résolution de marge par précédence → ajout des frais de livraison → prix final, avec arrondi à 2 décimales à chaque étape. **Le cas chiffré exact du CDC (8.3.1) a été vérifié au centime près** : 29,95 € → 1 422,63 MRU converti → +284,53 MRU de marge → +350,00 MRU de livraison → **2 057,16 MRU**, résultat identique à celui du cahier des charges. Retourne un `PriceBreakdown` exposant toutes les valeurs (taux, marge, source de la marge, frais) destinées à être historisées telles quelles sur une commande une fois le module Commande construit (Sprint 6, exigence 8.3.2).

**Endpoints admin** (permission `pricing.manage_margin`, déjà prévue dans la matrice confirmée) : historique des taux de change (ajout d'un nouveau taux, jamais de modification rétroactive), règles de marge (création avec validation que `scope_id` existe bien dans la table boutiques/catégories selon la portée), CRUD de la grille de frais de livraison, et un endpoint d'aperçu de prix par produit/variante — utile pour vérifier le calcul avant que le catalogue public (Sprint 5) ou le panier (Sprint 6) n'existent.

**Tests** : 22 nouveaux tests (Unit : cas de référence CDC, précédence des marges à deux niveaux, sélection du taux le plus récent, taux futur ignoré, sélection du bon palier de livraison, erreurs si taux/grille manquants ; Feature : CRUD et permissions des trois ressources admin, aperçu de prix) — 83 tests au total, tous verts. Migrations validées sur PostgreSQL réel.

---

## 7sexies. Sprint 5 — ce qui a été livré

**Recherche & filtres (7.2.2)** : `GET /api/v1/products` — mot-clé (nom fr/ar), boutique (slug), catégorie (slug), couleur, taille (sur variante en stock), fourchette de prix en MRU, tri (`newest` par défaut, `price_asc`, `price_desc`), pagination. Seuls les produits `active` de boutiques `active` apparaissent (règle 8.1.2, déjà en place depuis le Sprint 2).

**Fiche produit détaillée** : `GET /api/v1/products/{id}` — images, nom/description bilingues, boutique d'origine (nom/logo/pays, jamais l'URL source), catégorie, prix final en MRU par variante (calculé via `PricingService`), et un délai de livraison estimé. **Aucun prix EUR ni référence/URL boutique source n'est jamais exposé au client** (section 5 du CDC : le client n'a aucune relation directe avec la boutique) — vérifié explicitement par test.

**Limite technique assumée et documentée** : le prix final dépend de règles de marge/livraison résolues en PHP (pas exprimables en SQL simplement) ; la recherche par mot-clé porte sur un champ JSON bilingue. Les deux sont donc traités en mémoire après un premier filtrage SQL (boutique/catégorie/couleur/taille), plutôt qu'entièrement en base. Largement suffisant pour la taille de catalogue de cette session ; à revoir (ex. dénormaliser un prix final recalculé en base) si le catalogue grossit significativement — documenté dans `ProductSearchService`.

**Délai de livraison** : valeur statique de configuration (`config('catalog.delivery_estimate_days_*')`, 15–25 jours), faute de données logistiques réelles par étape/transporteur (Sprint 8). Signalé explicitement comme un placeholder, pas une estimation calculée.

**Cache** : le taux de change courant est désormais mis en cache Redis (5 min), invalidé immédiatement à l'ajout d'un nouveau taux — conforme à l'architecture décrite en 10.1 du CDC ("cache pour les données à forte fréquence de lecture : catalogue, taux de change").

**Tests** : 12 nouveaux tests (visibilité, recherche, filtres, tri, pagination, non-fuite de prix EUR/URL, fiche produit avec prix par variante, 404 si boutique/produit indisponible) — 95 tests au total, tous verts. Migrations validées sur PostgreSQL réel.

---

## 7septies. Sprint 6 — ce qui a été livré

**Panier** : `Cart`/`CartItem` (un panier par client, créé à la volée). `GET/POST/PUT/DELETE /api/v1/cart[/items/{id}]` — ajout (fusionne la quantité si la variante y est déjà), modification, suppression, avec vérification de propriété (403 si le panier n'appartient pas à l'utilisateur connecté) et refus d'ajouter une variante dont le produit ou la boutique n'est pas `active` (`khidmapp.product_unavailable`).

**Choix de conception signalé — statut "Brouillon"** : le CDC (8.4) liste "Brouillon" comme premier statut ("client ajoute des produits au panier"), mais aucune commande n'existe encore à ce stade fonctionnellement : le panier (`Cart`/`CartItem`) joue déjà ce rôle. La table `orders` n'est donc créée qu'au passage de commande, en démarrant directement à `paiement_en_attente` — ce choix évite des lignes `orders` orphelines pour des paniers jamais finalisés. `OrderStatus::DRAFT` existe pour compléter l'énumération des 15 statuts mais n'est jamais atteint par le parcours normal.

**State machine des 15 statuts (`OrderStatusTransitioner`)** : chaque transition est validée contre `OrderStatus::allowedNextStatuses()` (séquence normale, annulation depuis tout statut non terminal, remboursement uniquement depuis "Livré" ou depuis "Annulé") et journalisée intégralement dans `order_status_histories` (statut précédent/suivant, type d'acteur — système/client/service_client/administrateur —, note, horodatage — 8.4.1). Un remboursement exige toujours une note ; une annulation au-delà de la fenêtre gratuite (avant "Expédié par la boutique" — 8.4.1) exige également une note et marque `cancellation_fee_applicable = true`, faute de pouvoir prélever ces frais avant l'intégration du paiement (Sprint 7).

**Frais de livraison consolidés (8.6)** : `PricingService::subtotalForVariant()` calcule conversion + marge par ligne, sans frais de livraison ; `CartPricingCalculator` additionne les sous-totaux puis appelle `deliveryFeeForAmount()` **une seule fois** sur le total agrégé — le panier (`GET /cart`) et les deux parcours de commande (client et manuel) partagent ce même calcul, garantissant que le total simulé dans le panier est exactement celui facturé à la commande. Un test unitaire dédié (`CartPricingCalculatorTest`) démontre explicitement qu'une somme naïve par ligne aurait produit un résultat différent.

**Commande client** : `POST /api/v1/orders` (adresse du client + mode de paiement, valide que l'adresse lui appartient), historise taux de change/marge/frais au moment de l'achat sur chaque `OrderItem` (8.3.2), vide le panier après création. `GET /api/v1/orders`, `GET /api/v1/orders/{id}` (403 si pas le propriétaire). `POST /api/v1/orders/{id}/cancel` — auto-annulation réservée à la fenêtre gratuite ; au-delà, le client doit passer par le service client (`khidmapp.order_cancellation_blocked`).

**Supervision & commande manuelle admin (7.4, 8.4.2)** : `GET /api/v1/admin/orders` (filtrable par statut/client/boutique) et `GET .../{id}` (permission `orders.manage_status`) ; `PATCH .../status` fait transiter une commande via `OrderStatusTransitioner` en déduisant l'acteur (`administrateur` ou `service_client`) du rôle de l'agent connecté ; `POST /api/v1/admin/orders` (permission `orders.create_for_client`) crée une commande pour le compte d'un client (assistance téléphonique/vente assistée), réutilise le même `CartPricingCalculator`, et trace explicitement l'agent à l'origine via `created_by_agent_id` (exposé côté client par `is_manual_order`).

**`Boutique::canBeDeleted()` complété** : bloque désormais réellement la suppression d'une boutique référencée par une commande non terminale (`OrderItem` → commande dont le statut n'est ni `livre`, ni `annule`, ni `rembourse`) — l'implémentation provisoire du Sprint 2 (qui retournait toujours `true`) est levée.

**Bug détecté et corrigé pendant la vérification manuelle** : `OrderStatus::allowedNextStatuses()` traitait "Livré" comme un statut terminal générique, ce qui rendait la transition `livre → rembourse` inatteignable (retour anticipé `[]` avant d'évaluer le cas spécifique du remboursement). Corrigé en distinguant explicitement `annule` (seul statut à débuter par un retour anticipé, vers `rembourse`) et `rembourse` (aucune suite) des autres statuts, "Livré" retombant alors dans le cas général qui ajoute bien `rembourse` à la liste des transitions autorisées. Détecté via un test HTTP manuel bout-en-bout (`PATCH .../status` en `rembourse` depuis `livre`), pas par les tests automatisés écrits après coup — un test de régression dédié (`OrderStatusTransitionerTest::test_refund_succeeds_with_a_note`) couvre désormais ce cas.

**Tests** : 44 nouveaux tests (Unit : séquence des statuts, state machine — transitions légales/illégales, notes obligatoires, fenêtre d'annulation gratuite —, consolidation des frais de livraison ; Feature : panier — ajout/fusion/modification/suppression/isolation entre utilisateurs/produit ou boutique inactive —, commande client — passage de commande, annulation, isolation —, commandes admin — permissions par rôle, commande manuelle, transition de statut, filtre, remboursement —, `canBeDeleted` bloqué puis débloqué une fois la commande terminale) — 139 tests au total, tous verts. Style Pint conforme. Migrations validées sur PostgreSQL réel (`migrate:fresh` + seed).

---

## 7octies. Sprint 7 — ce qui a été livré

**Modèle unifié `payments`** : une seule table pour les deux modes (`method` = `bankily`/`manual`), conçue extensible (8.5.3) — chaque tentative crée un nouvel enregistrement (jamais réécrit après un statut terminal), ce qui conserve l'historique complet des échanges même après plusieurs preuves manuelles refusées/re-soumises pour une même commande.

**Paiement automatique Bankily (8.5.1)** : `POST /api/v1/orders/{id}/payments/bankily/initiate` crée (ou réutilise, si un paiement est déjà `pending` pour cette commande — idempotence) une transaction via `BankilyGateway` et retourne une référence + des instructions. La confirmation arrive de façon asynchrone via `POST /api/v1/webhooks/bankily`, qui transitionne automatiquement la commande vers "Paiement validé" (acteur `system`) sans intervention manuelle, comme l'exige le CDC. Le webhook est idempotent (une notification rejouée sur un paiement déjà terminal ne retraite rien) et ne réactive jamais une commande qui a quitté "Paiement en attente" entre-temps (ex. annulée par le client avant la confirmation).

**Paiement manuel (8.5.2)** : `POST /api/v1/orders/{id}/payment-proof` (upload d'une image, 5 Mo max) crée un enregistrement `pending`. Le service client/administrateur (permission `payments.validate_manual`, déjà prévue dans la matrice confirmée §4) consulte la file (`GET /api/v1/admin/payments/manual`), télécharge la preuve (`GET .../{id}/proof`, agnostique du disque de stockage — fonctionne aussi bien avec le disque `local` de cet environnement qu'avec S3/MinIO en production), puis valide (transitionne la commande vers "Paiement validé"), refuse (motif obligatoire, la commande reste "Paiement en attente", le client peut resoumettre une nouvelle preuve corrigée) ou demande un complément (note obligatoire, statut `info_requested`, commande inchangée).

**Règle transverse (8.5.3)** : un client ayant une preuve de paiement manuelle encore `pending` sur une de ses commandes ne peut pas en passer une nouvelle nécessitant un paiement (`khidmapp.pending_payment_proof_blocks_checkout`) — vérifié dans `OrderController::store()`. Le CDC prévoit une exception "configuration contraire de l'administrateur" : faute d'un module de réglages globaux modifiables depuis l'admin (aucun n'existe encore dans le projet), ce commutateur est pour l'instant `config('payments.block_new_orders_with_pending_manual_proof')`, pas un réglage exposé dans l'UI — voir §8.

**Point signalé explicitement — Bankily non fonctionnel** : le cahier des charges confirme Bankily comme moyen de paiement automatique mais ne fournit ni identifiants, ni format de requête/réponse, ni schéma de signature de webhook. `App\Services\Payment\StubBankilyGateway` génère une référence locale sans aucun appel réseau, et la protection du webhook (`X-Bankily-Signature`) est un simple partage de secret de développement (`BANKILY_WEBHOOK_SECRET`) en attendant le vrai mécanisme Bankily. **Ce n'est pas une intégration Bankily fonctionnelle** — à remplacer dès l'obtention d'un accès marchand réel (le contrat `BankilyGateway` est prêt à recevoir une vraie implémentation, comme pour `CatalogFetcher`/`SmsGateway`/`TranslatorGateway`).

**Corrections incidentes détectées pendant ce sprint** :
- Les exceptions de transition de commande (`InvalidOrderTransitionException`, `MissingTransitionNoteException`, introduites au Sprint 6) renvoyaient un message français codé en dur, jamais traduit en arabe malgré l'exigence bilingue FR/AR du projet — corrigé en les faisant passer par `lang/{fr,ar}/khidmapp.php` comme toutes les autres exceptions métier, avant de répliquer ce même défaut sur les nouvelles exceptions de paiement.
- Le message d'erreur "mode de paiement incorrect" était ambigu à la relecture manuelle ("cette commande utilise un autre mode de paiement (Bankily)" pouvait se lire comme "elle utilise déjà Bankily") — reformulé en "cette action nécessite le mode de paiement :method pour cette commande".
- Petite duplication éliminée : la déduction du type d'acteur (`administrateur` vs `service_client`) à partir du rôle de l'agent connecté, dupliquée entre `Admin\OrderController` et le nouveau contrôleur de paiements, a été centralisée dans `OrderActorType::forAgent()`.

**Tests** : 34 nouveaux tests (Unit : `BankilyPaymentService` — initiation, idempotence, webhook succès/échec, rejeu idempotent, non-réactivation d'une commande sortie de "en attente" —, `ManualPaymentReviewer` — soumission, validation, refus, demande de complément, ré-soumission après refus, double revue bloquée ; Feature : endpoints client — initiation Bankily, soumission de preuve, isolation entre utilisateurs —, webhook — signature invalide/valide/référence inconnue —, revue admin — permissions par rôle, file d'attente, téléchargement de la preuve, validation/refus/complément —, blocage du passage de commande) — 173 tests au total, tous verts. Style Pint conforme. Migrations validées sur PostgreSQL réel (`migrate:fresh` + seed) ; vérification manuelle bout-en-bout des deux parcours de paiement complets (Bankily et manuel, y compris cycle refus → complément demandé → re-soumission → validation) effectuée en HTTP réel avant de considérer le sprint terminé.

---

## 7nonies. Sprint 8 — ce qui a été livré

**Pas de nouvelle table pour les "10 étapes"** : le choix architectural du Sprint 6 (state machine des 15 statuts + `order_status_histories` historisant chaque transition avec horodatage) couvre déjà, presque terme à terme, les 10 étapes de la chaîne logistique du CDC (8.6) — la table `logistics_events` esquissée en §2 avant le début du développement aurait dupliqué `order_status_histories` sans rien apporter. Ajout d'une seule relation, `Order::latestStatusHistory()` (`hasOne(...)->latestOfMany()`), pour retrouver efficacement la date d'entrée dans le statut courant de chaque commande.

**Alertes de dépassement de délai (8.6.2)** : `LogisticsAlertService::currentAlerts()` compare, pour chaque commande active, le temps passé dans son statut courant à un délai indicatif configurable (`config('logistics.step_sla_hours')`) et retourne les commandes en dépassement, triées par ampleur décroissante — exposé en lecture seule via `GET /api/v1/admin/logistics/alerts` (permission `orders.manage_status`, déjà confirmée pour la supervision globale — 7.4). Calculé à la demande, sans table dédiée ni notification poussée : le module Notifications (Sprint 9) n'existe pas encore pour porter une "notification proactive au client" — seule la détection interne est livrée ce sprint.

**Tableau de bord logistique (8.6.2)** : `GET /api/v1/admin/logistics/dashboard` — nombre de commandes actives par statut, en temps réel, toutes les étapes non terminales incluses même à zéro (pour une vue complète de la chaîne). Volontairement limité à ce périmètre logistique ; le tableau de bord global (chiffre d'affaires, exports, KPI transverses — 7.4) reste au Sprint 11.

**Contrôle qualité (8.6.1)** : `POST /api/v1/admin/orders/{id}/items/{itemId}/quality-control` enregistre un rapport de conformité par article (modèle/couleur/taille + état visuel), réservé au statut "Contrôle qualité" de la commande ; motif obligatoire en cas de non-conformité. `GET .../quality-control` liste l'historique complet des rapports d'un article. Effectué "physiquement" par l'entrepôt 3PL mais enregistré par un agent Khidmapp (service client/administrateur), faute de rôle 3PL distinct dans le RBAC confirmé (7.1, 3 rôles seulement).

**Point signalé explicitement — lien réclamation non câblé** : le CDC (8.6.1) prévoit qu'une non-conformité déclenche "l'ouverture automatique d'une réclamation interne". Le module Réclamations n'existe pas encore (Sprint 10) : ce sprint se limite donc à consigner le rapport de non-conformité ; l'ouverture automatique d'une réclamation sera câblée dès que ce module existera.

**Délais indicatifs par statut — placeholders documentés** : le cahier des charges ne fournit de chiffre exact que pour deux étapes très en amont (confirmation Bankily < 30 s, traitement d'une preuve manuelle < 24 h ouvrées) et précise explicitement que le délai boutique → Madrid varie "selon boutique" sans cible fixe. Les onze valeurs de `config('logistics.step_sla_hours')` sont donc des estimations raisonnables, pas des données mesurées — même statut que l'estimation de livraison statique du Sprint 5, à remplacer dès que de vraies données opérationnelles existeront (idéalement différenciées par boutique/transporteur).

**Tests** : 13 nouveaux tests (Unit : `LogisticsAlertService` — dans les clous/en dépassement, commandes terminales jamais alertées, tri par ampleur décroissante ; Feature : contrôle qualité — permissions, motif obligatoire si non conforme, restriction au statut "Contrôle qualité", isolation article/commande, historique multi-rapports —, tableau de bord et alertes — permissions, comptage par statut, détection de dépassement) — 186 tests au total, tous verts. Style Pint conforme. Migrations validées sur PostgreSQL réel (`migrate:fresh` + seed) ; parcours complet vérifié en HTTP réel (commande avancée jusqu'à "Contrôle qualité", rapport de non-conformité, tableau de bord et alertes consultés).

---

## 7decies. Sprint 9 — ce qui a été livré

**Table unique `notification_logs`** (délibérément pas `notifications`, pour ne pas entrer en collision avec le système de notifications intégré de Laravel — le trait `Notifiable` déjà présent sur `User` depuis le Sprint 0 définit sa propre relation `notifications()` vers une table `notifications` au schéma différent, jamais utilisée dans ce projet) : historise chaque envoi individuel (un enregistrement par canal tenté), succès ou échec, avec le contenu rendu et le contexte — répond à l'exigence 8.7.2 ("chaque notification envoyée est historisée").

**`NotificationService`** : point d'entrée unique, rend le contenu bilingue dans la langue préférée du destinataire (`trans(..., $locale)`, jamais la locale de la requête courante — important puisqu'une action admin, ex. valider un paiement, notifie le *client*, pas l'agent), résout les canaux applicables et historise chaque tentative indépendamment (une panne sur un canal — ex. e-mail chez un client inscrit uniquement par téléphone — n'empêche pas les autres canaux, vérifié par test et en HTTP réel).

**Résolution des canaux (8.7.1, 8.7.2)** : push est le canal par défaut pour tout gabarit. SMS et e-mail sont restreints par gabarit (`config('notifications.sms_eligible_templates')` / `email_eligible_templates`) conformément au texte du CDC ("SMS pour les événements critiques (paiement, livraison)", "e-mail pour les récapitulatifs de commande" — aucun système de facture séparé n'existe, l'e-mail est donc limité à la confirmation de commande). Un gabarit critique (`config('notifications.critical_templates')` — paiement validé, annulation, remboursement ; seul le premier est cité explicitement par le CDC, les deux autres sont une extension raisonnable pour les événements à impact financier direct) est **garanti d'atteindre le client par au moins un canal** même si tous ses canaux sont désactivés — repli sur push, le seul canal sans coût ni dépendance externe — sans pour autant forcer spécifiquement push si un autre canal reste actif (8.7.2 exige "au moins un canal", pas un canal précis).

**Câblage des 10 déclencheurs (8.7)** : `OrderStatusTransitioner` déclenche automatiquement la notification associée à chaque statut notifiable après une transition réussie (`config('notifications.order_status_templates')` — seuls les statuts explicitement listés par le CDC déclenchent une notification ; les étapes internes sans notification client dédiée, ex. `achat_en_cours`, `expedie_boutique`, `consolidation`, n'en déclenchent aucune). La confirmation de commande (statut initial, jamais atteint via une transition) est déclenchée directement dans les deux contrôleurs de création de commande (client et manuel admin) ; l'anomalie de contrôle qualité (Sprint 8) dans `QualityControlController` lors d'un rapport non conforme.

**Endpoints** : `GET /api/v1/notifications` (historique du client connecté), `GET/PUT /api/v1/notification-preferences` (activer/désactiver chaque canal individuellement, valeur par défaut : tout activé), `GET /api/v1/admin/notifications` (consultation globale filtrable par client/canal/statut/gabarit, permission `notifications.view`, extension naturelle du RBAC comme `orders.manage_status`/`payments.validate_manual`).

**Points signalés explicitement — placeholders** :
- `App\Services\Notification\LogPushGateway` **n'est pas une intégration Firebase Cloud Messaging fonctionnelle** — aucun projet/identifiants Firebase n'existe (mêmes `FCM_PROJECT_ID`/`FCM_CREDENTIALS_PATH` vides que depuis le Sprint 0). Écrit dans les logs, comme `LogSmsGateway`.
- L'e-mail, en revanche, passe par le vrai système `Mail` de Laravel (`MAIL_MAILER=log` en développement) — ce n'est pas un placeholder custom, juste un pilote de développement standard ; à basculer vers un pilote réel (SMTP, SES, etc.) en production.

**Tests** : 21 nouveaux tests (Unit : `NotificationService` — canal par défaut, préférence désactivée, repli critique garanti, éligibilité SMS/e-mail par gabarit, isolation des échecs par canal, rendu dans la langue du destinataire ; Feature : préférences — valeurs par défaut, mise à jour partielle —, historique client — isolation entre utilisateurs —, consultation admin — permissions, filtre par statut —, déclencheurs bout-en-bout — confirmation de commande, transition de statut notifiable/non notifiable, anomalie de contrôle qualité conforme/non conforme) — 207 tests au total, tous verts. Style Pint conforme. Migrations validées sur PostgreSQL réel (`migrate:fresh` + seed) ; parcours complet vérifié en HTTP réel (commande → paiement validé → achat → réception Madrid → anomalie qualité → expédition → livraison, avec désactivation SMS à mi-parcours effectivement respectée).

---

## 7undecies. Sprint 10 — ce qui a été livré

**Réclamations (8.8)** : `Complaint`/`ComplaintMessage`, rattachées à une commande précise, catégorisées (produit non conforme/retard/dommage/erreur de facturation/autre), fil de discussion avec pièces jointes (images, jusqu'à 5 par message). Cycle de statuts confirmé par le CDC (Ouverte, En cours de traitement, En attente client, Résolue, Clôturée) — le CDC ne fournissant pas de table de transitions comme pour les commandes (8.4), les règles ci-dessous sont une interprétation raisonnable, documentée explicitement :
- une première réponse d'agent sur une réclamation "ouverte" vaut prise en charge (transition automatique vers "en_cours" — interprétation de 7.3 : "prise en charge, suivi, clôture") ;
- une réponse du client sur une réclamation "en attente client" la remet automatiquement "en cours" ;
- la clôture est réservée au service client/administrateur (permission `complaints.manage`, non ouverte au client — le CDC ne le lui accorde pas explicitement, contrairement à l'ouverture et à la messagerie, 7.2.4).

**Endpoints** : côté client, `GET/POST /api/v1/complaints`, `GET /api/v1/complaints/{id}`, `POST /api/v1/complaints/{id}/messages` (isolation stricte par propriétaire) ; côté service client/administrateur, `GET /api/v1/admin/complaints` (filtrable par statut/catégorie/client/commande), `POST .../{id}/messages`, `PATCH .../{id}/status`. Téléchargement des pièces jointes via une route partagée (`GET /api/v1/complaint-messages/{message}/attachments/{index}`) accessible au propriétaire de la réclamation ou à un agent — les deux audiences partagent la même ressource, d'où une vérification manuelle plutôt qu'un middleware `can:` classique.

**Centre d'aide / FAQ (8.8, première puce)** : `GET /api/v1/faqs` public, CRUD admin sous la nouvelle permission `content.manage`. Le CDC n'attribue ce module à aucun rôle précis ; réservé à l'administrateur (pas au service client), par analogie avec la gestion des boutiques/catégories — choix signalé, à confirmer si besoin.

**Point résolu — lien automatique vers une réclamation (8.6.1)** : la non-conformité de contrôle qualité (Sprint 8) ouvre désormais automatiquement une réclamation interne (`ComplaintService::openAutomatically()`, catégorie "produit non conforme", message système sans auteur humain — `complaint_messages.sender_id` rendu nullable pour ce cas, `sender_type = "system"`) — le point ouvert laissé dans les Sprints 8 et 9 est refermé. Le CDC demande aussi "l'information du service client" : interprétée ici comme la visibilité de la réclamation dans la file d'attente admin (`GET /admin/complaints`), pas comme une alerte active — aucun mécanisme de diffusion à toute l'équipe (liste de diffusion, canal dédié) n'existe.

**Extension signalée — notification de réponse agent** : une réponse d'agent sur une réclamation notifie le client (push uniquement, gabarit `complaint_reply`) — ce déclencheur ne fait pas partie des 10 événements listés en 8.7 mais complète naturellement le module Notifications pour ce nouveau flux ; explicitement hors périmètre littéral du CDC.

**Tests** : 31 nouveaux tests (Unit : `ComplaintStatus` — transitions autorisées/rejetées, statut terminal ; `ComplaintService` — ouverture, prise en charge automatique par réponse d'agent, retour "en cours" après réponse client, transitions invalides, ouverture automatique système ; Feature : réclamations client — ouverture avec/sans pièce jointe, isolation, message de suivi, validation message-ou-pièce-jointe —, réclamations admin — permissions, filtre, réponse + notification, transitions valides/invalides —, téléchargement de pièce jointe — propriétaire/agent/étranger —, FAQ publique et admin — permissions, CRUD, bilinguisme obligatoire —, intégration contrôle qualité → réclamation automatique) — 238 tests au total, tous verts. Style Pint conforme. Migrations validées sur PostgreSQL réel (`migrate:fresh` + seed) ; parcours complet vérifié en HTTP réel (ouverture avec photo, réponse d'agent avec notification, résolution, clôture, transition invalide rejetée, anomalie qualité → réclamation automatique visible côté client).

---

## 7duodecies. Sprint 11 — ce qui a été livré

**Gestion des utilisateurs (8.9.2)** : recherche/filtrage (rôle, nom, e-mail), blocage temporaire ou définitif d'un compte client (`blocked_until = null` = définitif), déblocage, création de comptes internes (service client/administrateur — les clients s'inscrivent eux-mêmes, jamais créés par cet endpoint). Le blocage agit à deux niveaux, JWT étant sans état : un contrôle au login (`AuthController::verifyOtp`/`loginEmail`, compte bloqué refusé avant émission du jeton) **et** un middleware global (`EnsureUserIsNotBlocked`, en tête du groupe `api`) qui invalide immédiatement un jeton déjà émis — vérifié en HTTP réel (jeton émis avant blocage, requête authentifiée refusée avec 403 juste après).

**Rôles (8.9.6)** : `PUT /api/v1/admin/users/{user}/roles` remplace intégralement l'ensemble des rôles d'un compte interne (`syncRoles`) ; l'attribution d'un rôle interne à un compte client est explicitement rejetée (422) — les deux univers ne se mélangent pas. Réactivation de la permission `roles.manage`, définie dès le Sprint 1 mais jamais câblée à une route jusqu'ici. Distinction volontaire entre `users.manage` (recherche/blocage — gestion des comptes) et `roles.manage` (création de comptes internes/attribution des rôles — gestion des droits), le CDC 7.4 les évoquant ensemble sans les distinguer formellement.

**Journal d'audit (8.9.6)** : nouvelle table `audit_logs` (acteur, action, sujet polymorphe, `changes` en JSON) et service `AuditLogger`, câblés sur exactement les cas cités par le CDC ("validation de paiement, modification de marge, changement de rôle") plus deux extensions raisonnables signalées explicitement dans le docblock d'`AuditAction` (blocage/déblocage de compte, création de compte interne) : modification de taux de change, modification de marge, validation/refus/demande de complément sur un paiement manuel, blocage/déblocage, création de compte interne, changement de rôle. Ne duplique pas `order_status_histories` (Sprint 6), qui reste la trace exhaustive des transitions de commande. Consultable via `GET /api/v1/admin/audit-logs` (filtrable par action/type de sujet/acteur), nouvelle permission `audit.view`.

**Tableau de bord & reporting (8.9.7, section 12)** : `DashboardReportService` calcule chiffre d'affaires, nombre de commandes, panier moyen, marge réalisée, ventilation des ventes par boutique/zone, top produits (quantité + chiffre d'affaires), commandes en cours vs livrées — filtrable par période, boutique, zone. Deux niveaux d'accès déjà prévus par le RBAC depuis le Sprint 1 mais jamais utilisés jusqu'ici (`dashboard.view_full` pour l'administrateur, `dashboard.view_limited` pour le service client) : le niveau limité reçoit les mêmes compteurs opérationnels (nombre de commandes, funnel, ventilations) mais **sans aucun chiffre financier** (CA, marge, chiffre d'affaires par boutique/zone/produit) — cohérent avec le reste de l'application où les données financières restent réservées à l'administrateur. Export CSV (`GET /api/v1/admin/dashboard/report.csv`, en-têtes bilingues fr/ar) réservé au niveau complet uniquement, un export étant par nature un document destiné à quitter l'application.

**Définition retenue pour le chiffre d'affaires (CA)** — assumption documentée, non explicitement définie par le CDC : somme de `total_mru` pour toutes les commandes hors statut "Annulée" (aucune transaction n'a eu lieu) ; les commandes "Remboursées" restent incluses, la vente et la livraison ayant eu lieu. Sert aussi de dénominateur au panier moyen et de périmètre à la marge réalisée, pour rester cohérent d'une métrique à l'autre.

**Limite signalée explicitement — pas de "commission" ni de marge nette** : aucun coût logistique (transport 3PL, entrepôt Madrid, douane) n'est enregistré nulle part dans l'application ; `margin_realized_mru` est une marge brute (majoration commerciale appliquée au prix boutique), pas un résultat net après coûts — voir le docblock de `DashboardReportService`.

**Deux colonnes ajoutées rétroactivement** pour rendre ce reporting possible : `orders.delivery_zone` (zone tarifaire figée au moment de la commande, déjà calculée par `CartPricingCalculator` mais jamais persistée jusqu'ici) et `order_items.margin_amount_mru_snapshot` (marge réalisée par unité en MRU, en complément de `margin_percent_snapshot`, pour ne pas la recalculer à partir du pourcentage). Les commandes passées avant ce sprint ont ces deux colonnes à `null` — invisibles dans les ventilations par zone/marge du tableau de bord tant qu'elles ne sont pas rejouées ou corrigées manuellement (aucune commande n'existait encore en production au moment de ce sprint).

**Hors périmètre de ce sprint — signalé explicitement** : **la gestion des promotions (CDC 8.9.5) n'a pas été traitée**. Aucune table, aucun modèle, aucun endpoint n'existe pour les codes promo/réductions. Ce module reste entièrement à faire dans un sprint dédié.

**Bug trouvé et corrigé par la vérification manuelle en HTTP** (pas par les tests automatisés) : l'attribut PHP `#[Fillable(...)]` du modèle `User` n'avait jamais été étendu avec les colonnes de blocage (`blocked_at`, `blocked_until`, `blocked_reason`, `blocked_by`) ajoutées dans ce même sprint — `$user->update([...])` dans `UserController::block()`/`unblock()` ignorait donc silencieusement ces champs (aucune exception, `Model::preventSilentlyDiscardingAttributes()` n'étant pas activé) : l'API répondait 200 avec un objet "bloqué" jamais réellement persisté en base. Corrigé, et un test de régression (`test_blocking_a_user_actually_persists_and_is_reflected_immediately`) vérifie désormais explicitement l'état en base, pas seulement la réponse HTTP.

**Tests** : 14 nouveaux tests (Feature : gestion des utilisateurs — blocage avec persistance réelle vérifiée en base, jeton déjà émis refusé après blocage, déblocage, création de compte interne, remplacement de rôles avec traçabilité `from`/`to`, rejet d'un rôle interne sur un client, accès refusé au service client ; journal d'audit — liste, filtre par action, accès refusé au service client ; tableau de bord — calcul CA/panier moyen/marge avec exclusion des commandes annulées, vue limitée sans champs financiers, accès client refusé, export CSV réservé au niveau complet) plus assertions d'audit ajoutées aux tests existants de paiement manuel/marge/taux de change — 252 tests au total, tous verts. Style Pint conforme. Migrations validées sur PostgreSQL réel (`migrate:fresh` + seed) ; parcours complet vérifié en HTTP réel (commande manuelle → livraison → tableau de bord complet et limité → export CSV → blocage/déblocage avec jeton déjà émis → création de compte interne → attribution de rôle → journal d'audit filtré).

---

## 7duodecies bis. Interface d'administration React — connexion à l'API

Le Sprint 0 avait livré un squelette React (navigation, i18n FR/AR, mise en page RTL) sans aucune page connectée à l'API — les onze sprints suivants ont porté exclusivement sur le backend. Ce travail complémentaire (hors découpage initial, demandé explicitement après coup) connecte ce squelette aux 96 opérations d'API déjà construites.

**Authentification** : connexion e-mail/mot de passe (`AuthProvider`/`useAuth`, contexte React + TanStack Query), jeton JWT en `localStorage`, intercepteur axios centralisant l'en-tête `Authorization` et gérant le 401 (jeton expiré/invalide → purge + retour à `/login` ; le 403, lui, n'entraîne volontairement pas de déconnexion, qu'il s'agisse d'un compte bloqué ou d'un simple refus de permission sur une route donnée — les deux cas restent des erreurs affichées sur place, pas une invalidation de session). `ProtectedRoute` protège l'ensemble des pages ; la navigation se adapte au rôle réel de l'utilisateur connecté (`GET /me`) plutôt qu'à une liste statique — un service client ne voit ni Boutiques, ni Utilisateurs, ni Rôles, ni Rapports, cohérent avec la matrice de permissions (§4).

**Six pages connectées** : Tableau de bord (avec bascule automatique niveau complet/limité, cf. Sprint 11), Boutiques (CRUD + changement de statut), Commandes (liste + détail + transition de statut, la validation de la transition restant entièrement côté API — le formulaire propose les 15 statuts et relaie tel quel le message d'erreur 422 en cas de transition invalide, sans dupliquer `OrderStatus::allowedNextStatuses()` en JavaScript), Paiements manuels (file d'attente + validation/refus/complément), Utilisateurs (recherche + blocage/déblocage), Rôles (création de compte interne + réattribution), Rapports (journal d'audit filtrable).

**Vérification** : chaque page a été vérifiée en navigateur réel (Playwright, contre le backend et PostgreSQL réels, pas de mock) avant d'être considérée terminée — y compris des cas d'erreur volontaires (transition de statut invalide, accès refusé par rôle). Un bug de cache a été rencontré et corrigé pendant cette vérification : le cache de permissions Spatie (`PermissionRegistrar`), stocké dans Redis, n'était pas rafraîchi par un processus `php artisan serve` déjà démarré au moment d'un `migrate:fresh --seed` lancé depuis un autre processus — purement un artefact de l'environnement de vérification manuelle (`php artisan permission:cache-reset` après tout reseed résout le symptôme), pas un bug applicatif.

**Limites signalées explicitement** :
- **Bilinguisme incomplet** : le contenu des six pages connectées (libellés, formulaires, messages) est en français en dur, pas routé via `i18next` — seul le squelette hérité du Sprint 0 (navigation, en-tête, connexion) est réellement bilingue avec bascule RTL fonctionnelle. Vérifié en changeant la langue sur la page Commandes : la mise en page bascule bien en RTL mais le texte reste français. À corriger avant mise en production.
- **Commande manuelle** (8.4.2) non construite côté admin — l'endpoint existe, le sélecteur d'articles nécessaire ne l'est pas.
- Aucun test automatisé (unitaire/E2E) pour cette app — vérification manuelle uniquement, comme documenté dans `admin/README.md`.

---

## 7duodecies ter. Produit personnalisé ("pedido personalizado") — extension hors CDC

Demande explicite après coup, sur le modèle d'une app tierce de type "achat par proxy" (nom non repris, identité visuelle propre à Khidmapp) : permettre au client de faire acheter un produit trouvé lui-même sur le site d'une boutique, plutôt que limité au catalogue synchronisé. Clarifications tranchées avec l'utilisateur avant implémentation :
- **Frais de service** : le moteur de marge existant (`PricingService`/`margin_rules`, précédence catégorie > boutique > global, §5) est réutilisé tel quel — pas de barème séparé pour ce flux.
- **Prix estimé saisi par le client** : engageant, utilisé directement par le moteur de marge pour calculer le prix final (pas de re-saisie manuelle par l'administration).
- **Modélisation de la revue** : entité séparée (`custom_order_requests`/`custom_order_items`), distincte de `orders` — aucune commande n'existe tant que l'administration n'a pas confirmé la faisabilité. Seule l'approbation crée réellement la ligne `orders` (statut initial habituel `paiement_en_attente`), avec un lien traçable (`order_items.custom_order_item_id`, nullable) vers la ligne de demande d'origine (URL produit, notes taille/couleur — absentes du schéma `order_items` standard).
- **Paiement** : collecté après confirmation/tarification par l'administration, jamais avant — cohérent avec le fait que le prix final dépend de la marge résolue à l'approbation.

**Étapes simplifiées côté client** (`App\Support\CustomOrderStage`) : Révision → Achat → Réception et contrôle qualité à Madrid → Expédition internationale → Livraison à Nouakchott — un mapping d'affichage pur (aucune nouvelle state machine) qui regroupe les 15 statuts détaillés d'`OrderStatus` (§3) une fois la demande convertie en commande ; avant conversion, l'étape est toujours "Révision".

**Backend** : migrations `custom_order_requests`/`custom_order_items` + colonne `order_items.custom_order_item_id` ; modèles `CustomOrderRequest`/`CustomOrderItem` ; `CustomOrderRequestService` (approve/reject) ; endpoints client (`/custom-order-requests`) et admin (`/admin/custom-order-requests`, réutilisant la permission `orders.manage_status` — même principe de supervision que les commandes classiques, §4) ; notification `ORDER_CONFIRMED` réutilisée à l'approbation (devient une vraie commande), nouveau gabarit `CUSTOM_ORDER_REJECTED` au rejet. 10 tests Feature dédiés (soumission, ownership, permissions, calcul de prix avec marge boutique vs. globale, rejet avec motif obligatoire, double revue bloquée).

**Admin React** : nouvelle page "Produits personnalisés" (file d'attente filtrable par statut, détail des articles avec lien produit, confirmation → création de commande / rejet avec motif obligatoire).

**Mobile** : nouvel onglet "Accueil" (premier onglet, remplace l'ancien point d'entrée direct sur Catalogue) — accueil nominatif, recherche de boutique, grille des boutiques actives (`GET /boutiques`, tape → catalogue filtré par boutique via le paramètre `boutique` déjà supporté par `SearchProductsRequest`), bouton "Commande personnalisée" (formulaire multi-produits : lien, quantité, prix affiché, notes), liste + détail de mes demandes avec le traceur des 5 étapes.

**Limites signalées explicitement** :
- **Bilinguisme incomplet sur les nouveaux écrans mobiles** (Accueil, formulaire, liste/détail de commande personnalisée) : mêmes limites que le reste de l'app mobile (voir `mobile/README.md`) — libellés d'écran traduits via ARB, mais pas de vérification visuelle RTL faute d'émulateur/appareil disponible dans cette session.
- **Aucune modification/annulation d'une demande "en attente"** côté client une fois soumise — seule l'administration peut la faire évoluer (confirmer/rejeter).
- **Correspondance boutique par URL non automatisée** : le client choisit optionnellement une boutique dans une liste (pour bénéficier d'une marge boutique plutôt que globale) ; rien ne vérifie que l'URL saisie correspond réellement au domaine de la boutique choisie.

---

## 7duodecies quater. Marge par palier de prix + coût de gestion visible — révision du moteur de tarification (extension hors CDC)

Demande explicite après coup, sur le modèle d'une app tierce de type "achat par proxy" (captures d'écran fournies) : le client ne doit jamais voir de pourcentage de marge ni de prix EUR (règle transverse déjà en vigueur), mais l'app doit désormais lui montrer une ligne "Coût de gestion" distincte, en plus du sous-total et de la livraison. Clarifications tranchées avec l'utilisateur avant implémentation :
- **Marge (toujours invisible au client)** : par palier de prix EUR affiché — sous 200 € : 20 %, de 200 à 700 € : 15 %, au-delà de 700 € : 10 % (`App\Support\PriceMarginTier`). Remplace, comme dernier maillon de la précédence, l'ancien défaut plat (`config('pricing.default_margin_percent')`) évoqué au §7quinquies — **révise donc la précédence confirmée à cette époque** : catégorie > boutique > global (règle explicite, désormais réservée à une campagne ponctuelle décidée par un administrateur, plus seedée par défaut) > palier de prix. Une migration (`remove_auto_seeded_global_margin_rules`) retire la règle globale plate que `PricingSeeder` créait automatiquement, pour que les paliers s'appliquent réellement sur les installations existantes.
- **Coût de gestion (visible au client)** : 5 % (`config('pricing.management_fee_percent')`), calculé sur (sous-total article + frais de livraison) — vérifié au centime près contre l'exemple chiffré fourni par l'utilisateur (21,90 € + 9,95 € de livraison, ×5 % = 1,59). Appliqué uniformément au catalogue, au panier et à la commande personnalisée (même moteur, `CartTotals::managementFeeMru` dans les deux calculateurs de prix) ; nouvelle colonne `orders.management_fee_mru`, exposée par `OrderResource` et `GET /api/v1/cart`.

**Limites signalées explicitement, non implémentées dans cette révision** :
- **Champ code promo** : évoqué dans la même demande, réponse utilisateur non exploitable ("Something else" sans texte de suivi) — aucun champ, table ni logique de code promo n'existe côté backend (voir aussi le point 15 des sujets ouverts, promotions CDC 8.9.5, plus large et déjà non traité).
- **Refonte visuelle du checkout façon étapes numérotées** (Adresse → Mode de livraison → Paiement) évoquée dans la demande initiale : non construite dans cette révision, qui s'est concentrée sur le moteur de calcul (marge + coût de gestion) et l'affichage de la nouvelle ligne dans les écrans de totaux existants (panier, détail de commande mobile, détail de commande admin).

---

## 7duodecies quinquies. Livraison par poids de colis choisi au paiement (extension hors CDC)

Suite directe du point ci-dessus : l'utilisateur a fourni les tarifs réels par poids (capture d'écran de l'app de référence à l'appui, montrant un sélecteur "Poids de la commande" à choix unique au paiement) et a tranché deux ambiguïtés explicitement :
- **Qui saisit le poids** : le client lui-même, en le choisissant parmi 3 paliers au moment du paiement (comme dans la capture) — pas une donnée produit stockée à l'avance (le catalogue actuel n'a pas de poids par produit) ni une donnée que l'administration devrait renseigner a posteriori.
- **Portée** : s'applique au panier/checkout classique **et** à la commande personnalisée (même principe que le reste de cette révision de tarification).
- **Zone** : les tarifs fournis (`petit` 0-4 kg = 600 MRU, `moyen` 4-7 kg = 1200 MRU, `tres_grand` 7-15 kg = 1700 MRU + 200 MRU/kg au-delà de 15 kg) sont **spécifiques à Nouakchott** ; les autres zones (non utilisées en pratique dans cette session, la zone étant toujours Nouakchott par défaut) retombent sur la grille par tranche de prix existante (§7quinquies) faute de chiffres.

**Implémentation** : `App\Support\PackageWeightTier` (paliers + surcharge kg supplémentaire) ; `PricingService::deliveryFee()` — nouveau point d'entrée qui préfère le poids choisi sur Nouakchott, retombe sur `deliveryFeeForAmount()` sinon (aperçu panier avant l'étape de paiement, autres zones) ; `weight_tier`/`extra_weight_kg` ajoutés aux deux calculateurs de prix, à `orders` et `custom_order_requests` (reporté sur la Commande réelle à l'approbation), requis à la soumission (`POST /orders`, `POST /admin/orders`, `POST /custom-order-requests`). Mobile : sélecteur à 3 cartes (`WeightTierSelector`, clés et tarifs codés en dur en miroir du backend — même principe que les modes de paiement déjà codés en dur des deux côtés) sur l'écran de paiement du panier et sur le formulaire de commande personnalisée, avec champ de poids supplémentaire optionnel révélé pour le plus grand palier ; affiché ensuite dans le détail de commande (mobile et admin).

---

## 8. Points encore ouverts

1. Détail fin des permissions par sous-action au sein de chaque module (la matrice CDC 7.5 est une synthèse ; la granularité complète sera affinée module par module au fil des sprints, avec validation à chaque fois).
2. **Opérateur SMS pour l'envoi réel des OTP** : non précisé par le cahier des charges — à trancher avant mise en production (voir §7bis).
3. Upload réel de logo/bannière boutique (actuellement de simples URL) — à raccorder au stockage S3/MinIO si un flux d'upload dédié est souhaité plutôt que de simples liens externes.
4. **Un vrai `CatalogFetcher` par boutique** (scraping respectueux des CGU ou accord avec les boutiques) et **un vrai `TranslatorGateway`** — non spécifiés par le cahier des charges, à trancher avant mise en production (voir §7quater).
5. Poids/volume produit non modélisés (choix Sprint 4 : grille de livraison par tranche de prix) — à réévaluer si une grille par poids/volume s'avère nécessaire plus tard (voir §7quinquies).
6. **Délai de livraison affiché statique** (15–25 jours, non calculé) et **recherche/tri par prix en mémoire plutôt qu'en SQL** — deux limites techniques assumées à revisiter avec de vraies données logistiques et/ou un catalogue à plus grande échelle (voir §7sexies).
7. **Frais d'annulation tardive marqués mais jamais prélevés** (`cancellation_fee_applicable = true`) : la validation d'un paiement Bankily/manuel confirme la commande, mais aucun mécanisme de prélèvement de frais d'annulation tardive n'existe — la commande est juste signalée au service client pour traitement manuel (voir §7septies).
8. **Vraie intégration Bankily** : identifiants marchands, format d'API réel, schéma de signature de webhook — tout est à obtenir/spécifier par Bankily avant mise en production ; `StubBankilyGateway` n'est qu'un simulateur local (voir §7octies).
9. **Commutateur "bloquer une nouvelle commande si preuve en attente" non exposé dans l'UI admin** (8.5.3 prévoit une exception "configuration contraire de l'administrateur") : c'est pour l'instant une valeur de configuration statique, faute d'un module de réglages globaux modifiables depuis l'administration (voir §7octies).
10. **Délais SLA logistiques indicatifs, pas mesurés** (`config('logistics.step_sla_hours')`) — voir §7nonies.
11. **Vraie intégration Firebase Cloud Messaging** : aucun projet/identifiants Firebase n'existe ; `LogPushGateway` n'est qu'un simulateur local (voir §7decies).
12. **Alertes de dépassement de délai (Sprint 8) toujours non poussées au client** : le module Notifications existe désormais, mais `LogisticsAlertService` n'est pas encore relié à `NotificationService` — cette liaison ("notification proactive au client" en cas de dépassement, 8.6.2) reste à faire, faute d'avoir été explicitement redemandée pour ce sprint (voir §7nonies/§7decies).
13. **"Information du service client" (8.6.1) interprétée comme simple visibilité, pas comme alerte active** : aucun mécanisme de diffusion à toute l'équipe (liste de diffusion, canal dédié) n'existe pour signaler activement une nouvelle réclamation automatique aux agents — ils doivent consulter la file d'attente (voir §7undecies).
14. **FAQ réservée à l'administrateur, pas au service client** : le CDC n'attribue ce module à aucun rôle précis ; choix par analogie avec la gestion des boutiques/catégories, à confirmer si besoin (voir §7undecies).
15. **Gestion des promotions (CDC 8.9.5) non traitée** : aucune table, modèle, ni endpoint pour les codes promo/réductions — entièrement à faire dans un sprint dédié (voir §7duodecies).
16. **CA/marge du tableau de bord calculés sur `total_mru`/`margin_amount_mru_snapshot` avec les commandes annulées exclues et les remboursées incluses** : définition raisonnable mais non explicitement tranchée par le CDC, à confirmer si un besoin de reporting plus fin (net des remboursements, par exemple) se présente (voir §7duodecies).
17. **`orders.delivery_zone` et `order_items.margin_amount_mru_snapshot` à `null` sur les commandes antérieures au Sprint 11** : ces commandes n'apparaissent pas dans les ventilations par zone/marge du tableau de bord tant qu'elles ne sont pas corrigées manuellement (voir §7duodecies).
18. **Aucune donnée de coût logistique suivie** (transport 3PL, entrepôt Madrid, douane) : le tableau de bord ne peut donc calculer ni commission ni marge nette, seulement une marge brute — à ajouter si le suivi des coûts devient un besoin (voir §7duodecies).
19. **Admin React : bilinguisme incomplet** — les six pages connectées à l'API (Sprint 11 bis) affichent leur contenu en français en dur, pas via `i18next` ; seul le squelette hérité du Sprint 0 (navigation, en-tête, connexion) est réellement bilingue FR/AR avec RTL fonctionnel. À corriger avant mise en production (voir §7duodecies bis).
20. **Admin React : pas de commande manuelle** (8.4.2) ni de tests automatisés (unitaire/E2E) — vérification manuelle en navigateur uniquement pour cette app (voir §7duodecies bis).
