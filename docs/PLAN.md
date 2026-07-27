# Khidmapp — Plan de développement détaillé (v0.1, brouillon pour validation)

> Statut : **brouillon soumis à validation**. Rien dans ce document ne doit être considéré comme définitif tant que les points marqués **[À VALIDER]** n'ont pas été tranchés. Le « cahier des charges fonctionnel et technique v1.0 » mentionné dans le prompt de démarrage n'a pas encore été fourni dans ce dépôt/cette session — ce plan est donc bâti uniquement à partir du prompt de développement reçu. Toute règle métier ci-dessous marquée **[HYPOTHÈSE]** devra être confirmée ou corrigée à réception du cahier des charges.

---

## 0. Repo et organisation générale

Un seul dépôt GitHub est en scope (`bouneabeiderrahmane-max/khidmapp`) : on part donc sur un **monorepo**.

```
khidmapp/
├── backend/          # API Laravel (REST, PostgreSQL, Redis, JWT)
├── mobile/           # App Flutter (Android + iOS)
├── admin/            # Interface d'administration web (React)
├── docs/             # Documentation technique & fonctionnelle (ce plan, ADRs, OpenAPI, etc.)
├── docker-compose.yml
└── README.md
```

Chaque module fonctionnel (section 6 du prompt) sera développé dans son propre commit/série de commits, avec à chaque fois : migrations, modèles, services/contrôleurs, routes API, tests, mise à jour OpenAPI, note de synthèse.

---

## 1. Choix techniques précisés (dans le cadre imposé)

| Sujet | Choix proposé | Justification |
|---|---|---|
| Backend | Laravel 11 (PHP 8.3) | Version LTS-like actuelle, imposée par le cahier des charges |
| Auth JWT | `php-open-source-saver/jwt-auth` | Fork activement maintenu de `tymon/jwt-auth` (qui est à l'abandon) ; access + refresh token |
| Autorisation (rôles/permissions) | `spatie/laravel-permission` | Standard de facto Laravel pour un RBAC piloté par base de données (pas de droits en dur dans les contrôleurs) — **[À VALIDER]** : accord pour ce package, ou préférence pour un système de rôles/permissions maison ? |
| i18n backend | Fichiers `resources/lang/{fr,ar}` pour les libellés statiques (UI, notifications, e-mails) + colonnes JSON `{ "fr": "...", "ar": "..." }` pour le contenu dynamique traduisible (nom produit, catégorie, description) | Évite le texte figé dans une seule langue partout, y compris pour le contenu synchronisé depuis les boutiques |
| RTL / i18n mobile | `flutter_localizations` + fichiers ARB + `Directionality` piloté par la locale | Flutter gère nativement le RTL complet (layout, pas seulement le texte) |
| Admin web | React 18 + Vite + TypeScript + TanStack Query + Tailwind + shadcn/ui + `react-i18next` (RTL via `dir` dynamique) | Stack moderne, légère, pas de SSR nécessaire pour un back-office interne ; TanStack Query simplifie la synchro avec l'API REST ; shadcn/ui accélère les écrans de gestion (tables, formulaires) tout en restant personnalisable |
| Base de données | PostgreSQL 16 | Imposé |
| Cache / queues | Redis (cache + Laravel queues pour sync catalogue, notifications, etc.) | Imposé + usage naturel pour les jobs asynchrones |
| Stockage fichiers | S3 (ou compatible, ex. MinIO en local/dev) via le driver `s3` de Laravel Filesystem | Imposé |
| Notifications push | Firebase Cloud Messaging via Laravel Notification channel custom | Imposé |
| Paiement auto | Intégration API Bankily (service dédié + webhook) | Imposé |
| Documentation API | OpenAPI 3.1 généré/maintenu via `dedoc/scramble` ou annotations `zircote/swagger-php`, servi par Swagger UI | À trancher en sprint 0 selon l'ergonomie constatée |

---

## 2. Modèle de données (v0.1)

Entités de la section 7, détaillées en tables concrètes. Toutes les tables `products`, `boutiques`, etc. ont `soft deletes` où pertinent.

### Identité & autorisation
- **users** : id, name, phone (unique), email (nullable, unique), password, locale (fr/ar), phone_verified_at, status, timestamps, soft deletes
- **otp_codes** : id, user_id/phone, code_hash, channel(sms), expires_at, consumed_at
- **addresses** : id, user_id, label, city, area, geo_lat/lng, phone, is_default, timestamps
- **roles**, **permissions**, **role_has_permissions**, **model_has_roles** (tables spatie/laravel-permission)

### Catalogue
- **boutiques** : id, name, slug, logo_url, banner_url, base_url, country_code, currency_code, status, default_margin_percent, sync_config (jsonb: fréquence, mapping catégories, credentials scraping/API), soft deletes
- **categories** : id, name (jsonb fr/ar), parent_id, slug
- **boutique_categories** : mapping boutique → catégorie interne (pour la normalisation lors de la synchro)
- **products** : id, boutique_id, external_ref, category_id, name (jsonb), description (jsonb), images (jsonb), base_price_eur, status (active/out_of_stock/discontinued), last_synced_at, soft deletes
- **product_variants** : id, product_id, sku, size, color, price_eur, stock_status, external_variant_ref

### Tarification
- **exchange_rates** : id, currency_pair (EUR_MRU), rate, effective_at, created_by
- **margin_rules** : id, scope_type (global/boutique/category), scope_id (nullable pour global), percent, effective_at, created_by — résolution de précédence **[À VALIDER]** : catégorie > boutique > global ?
- **delivery_fee_tiers** : id, min_weight_kg, max_weight_kg, min_volume_m3, max_volume_m3, fee_mru, zone (préparation multi-transporteur future)

### Commande
- **carts**, **cart_items** (variant_id, qty, prix simulé)
- **orders** : id, user_id, status, address_id, subtotal_eur, exchange_rate_snapshot, margin_percent_snapshot, delivery_fee_snapshot_mru, total_mru, payment_method, timestamps
- **order_items** : id, order_id, product_variant_id, qty, unit_price_eur, unit_price_mru_snapshot
- **order_status_history** : id, order_id, from_status, to_status, actor_type (system/user), actor_id, note, created_at — traçabilité complète des transitions

### Paiement
- **payments** : id, order_id, method (bankily/manual), status (pending/validated/rejected), proof_file_url, external_ref, validated_by, validated_at, amount_mru

### Logistique
- **shipment_steps** ou réutilisation de `order_status_history` filtré sur les statuts logistiques (achat → réception Madrid → contrôle qualité → consolidation → expédition → arrivée Nouakchott → livraison) — **[À VALIDER]** : une table dédiée `logistics_events` avec SLA/délai attendu par étape est probablement nécessaire pour les alertes de dépassement de délai (section 8).

### Notifications & support
- **notifications** : id, user_id, channel (push/sms/email), locale, template_key, payload (jsonb), status, sent_at
- **notification_preferences** : user_id, channel, enabled
- **complaints** : id, order_id, user_id, subject, status, priority
- **complaint_messages** : complaint_id, sender_type, sender_id, message, attachments (jsonb)

### Synchronisation & audit
- **sync_logs** : id, boutique_id, started_at, finished_at, status, products_synced, products_failed, errors (jsonb)
- **audit_logs** : id, actor_type, actor_id, action, subject_type, subject_id, before (jsonb), after (jsonb), created_at — couvre changements de statut, validation/refus paiement, modification marge/rôle (exigence section 4)

Index prévus : `orders.status`, `orders.user_id`, `products.boutique_id`, `products.category_id`, `order_status_history.order_id`, `payments.status`.

---

## 3. Cycle de statuts de commande — brouillon **[HYPOTHÈSE — À VALIDER EN PRIORITÉ]**

Le prompt mentionne 15 statuts définis dans le cahier des charges (« de Brouillon à Livré/Annulé/Remboursé ») mais ce document n'est pas encore disponible ici. Proposition de state machine à 15 états, à confirmer/corriger :

1. Brouillon (panier non validé)
2. En attente de paiement
3. Paiement en cours de vérification (preuve manuelle uploadée)
4. Paiement refusé
5. Payée / Confirmée
6. Achat en cours (auprès de la boutique)
7. Achat échoué / anomalie (produit indisponible, prix différent → retour vers le client pour arbitrage)
8. Achetée
9. Reçue à l'entrepôt Madrid
10. Contrôle qualité
11. Consolidée (groupage avec d'autres articles/commandes)
12. Expédiée (transport international)
13. Arrivée à Nouakchott
14. En livraison locale
15. Livrée / Annulée / Remboursée (statuts terminaux)

Cette liste est **indicative** — je ne coderai pas la state machine tant qu'elle n'est pas confirmée, car elle conditionne les transitions autorisées, les règles d'annulation, et les déclencheurs de notifications (section 6.6 et 6.9 du prompt).

---

## 4. Matrice de permissions — squelette **[À VALIDER]**

Rôles proposés : `super_admin`, `admin`, `service_client`, `client`. Permissions envisagées (non exhaustif, granularité à affiner module par module) :
`boutiques.manage`, `products.sync`, `pricing.manage`, `orders.view_all`, `orders.manage_status`, `payments.validate`, `complaints.manage`, `users.manage`, `roles.manage`, `reports.view`.

Piloté entièrement en base (via `spatie/laravel-permission`), jamais de `if ($user->role === 'admin')` en dur dans les contrôleurs — utilisation de `Gate`/policies + middleware `permission:xxx`.

---

## 5. Moteur de calcul de prix — séquence

```
prix_boutique_eur
  → conversion EUR → MRU (taux du jour, table exchange_rates)
  → application marge (résolue par précédence catégorie > boutique > global — [À VALIDER])
  → ajout frais de livraison (grille poids/volume, delivery_fee_tiers)
  → prix final MRU affiché au client
```

Chaque commande fige au moment de l'achat : `exchange_rate_snapshot`, `margin_percent_snapshot`, `delivery_fee_snapshot_mru` — indépendamment de l'évolution ultérieure des paramètres globaux (exigence section 4).

**[À VALIDER]** : le frais de livraison est-il estimé à la commande (poids théorique du produit) puis ajusté au moment de la consolidation (poids réel mesuré à l'entrepôt), ou figé dès la commande ? Impact direct sur le workflow logistique et sur d'éventuels compléments de paiement.

---

## 6. Découpage en sprints (ordre = section 6 du prompt)

| Sprint | Contenu | Livrables |
|---|---|---|
| 0 | Socle technique : scaffolding Laravel/Flutter/React, Docker Compose (Postgres+Redis+MinIO), CI, RBAC de base, JWT, squelette OpenAPI, squelette i18n FR/AR + RTL | Repo qui build/run, migrations initiales, `/api/v1/health` |
| 1 | Authentification & comptes | OTP téléphone, JWT+refresh, profils, adresses multiples, rôles internes |
| 2 | Boutiques | CRUD, paramètres de synchro par boutique |
| 3 | Synchronisation catalogue | Jobs Redis, mapping catégories, journal de synchro, gestion anomalies |
| 4 | Moteur de calcul de prix | Conversion, marges (global/boutique/catégorie), frais de livraison, historisation |
| 5 | Catalogue & recherche | Recherche, filtres, fiche produit bilingue |
| 6 | Panier & commande | State machine statuts, règles de transition/annulation |
| 7 | Paiements | Bankily auto + paiement manuel (upload preuve + validation service client) |
| 8 | Logistique | 10 étapes de suivi, alertes SLA |
| 9 | Notifications | FCM + SMS + e-mail, bilingues, préférences utilisateur |
| 10 | Support & réclamations | FAQ, réclamations liées à une commande, messagerie |
| 11 | Administration & pilotage | Dashboard, exports CSV/Excel, gestion marges/devises/frais/promos/rôles |

Chaque sprint : migrations + modèles + services/contrôleurs + routes + tests (unitaires sur logique métier critique, intégration sur endpoints commande/paiement) + doc OpenAPI + note de synthèse.

---

## 7. Points explicitement en attente de validation avant de coder

1. **Cahier des charges v1.0** : non fourni dans cette session — à transmettre pour trancher tout ce qui est marqué [À VALIDER]/[HYPOTHÈSE] ci-dessus.
2. Liste et libellés exacts des 15 statuts de commande + règles de transition/annulation.
3. Matrice de permissions précise par rôle (quelles actions pour `service_client` vs `admin` vs `super_admin`).
4. Précédence des règles de marge (catégorie/boutique/global) en cas de conflit.
5. Frais de livraison : figés à la commande ou ajustés à la consolidation ?
6. Accord sur `spatie/laravel-permission` comme brique RBAC (ou préférence pour une implémentation maison).

