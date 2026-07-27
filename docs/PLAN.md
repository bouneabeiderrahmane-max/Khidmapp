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
10. **Délais SLA logistiques indicatifs, pas mesurés** (`config('logistics.step_sla_hours')`) et **alertes de dépassement non poussées** (détection interne seulement, faute du module Notifications — Sprint 9) — voir §7nonies.
11. **Non-conformité qualité sans lien automatique vers une réclamation** : le module Réclamations n'existe pas encore (Sprint 10) ; le rapport de non-conformité est consigné mais n'ouvre rien automatiquement pour l'instant (voir §7nonies).
