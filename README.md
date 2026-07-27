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
- **Sprint 2** (boutiques) : terminé — CRUD admin, statuts (Active/Inactive/En pause/En test), catalogue public filtré, 8 boutiques de référence seedées.
- **Sprint 3** (synchronisation catalogue) : terminé — moteur de synchro (upsert produits/variantes, détection d'anomalies, délai de grâce configurable, préservation du catalogue en cas d'échec), mapping de catégories, correction manuelle des traductions (verrouillée contre les resynchronisations), job Redis + planificateur par fréquence de boutique.
- **Sprint 4** (moteur de calcul de prix) : terminé — conversion EUR→MRU, précédence des marges catégorie > boutique > global (confirmée), grille de frais de livraison par tranche de prix, historique des taux. Le cas chiffré du CDC (29,95 € → 2 057,16 MRU) est vérifié au centime près.
- **Sprint 5** (catalogue & recherche) : terminé — recherche publique (mot-clé, boutique, catégorie, couleur, taille, fourchette de prix, tri), fiche produit détaillée avec prix final en MRU par variante, jamais de prix EUR/URL boutique exposés au client. Cache Redis du taux de change.
- **Sprint 6** (panier & commande) : terminé — panier client, passage de commande (historisation taux/marge/frais par ligne), state machine des 15 statuts avec traçabilité complète (`OrderStatusTransitioner`), frais de livraison consolidés une seule fois par commande, auto-annulation dans la fenêtre gratuite, supervision admin + commande manuelle assistée, `Boutique::canBeDeleted()` désormais pleinement fonctionnel.
- **Sprint 7** (paiements) : terminé — paiement automatique Bankily (initiation idempotente + confirmation asynchrone par webhook, transition automatique vers « Paiement validé »), paiement manuel (soumission de preuve, revue service client : validation/refus motivé/demande de complément), historique complet conservé à chaque re-soumission, blocage transverse d'une nouvelle commande si une preuve est en attente.
- **Sprint 8** (logistique) : terminé — alertes de dépassement de délai par statut (`LogisticsAlertService`, calculées sur l'historique de statuts déjà existant, sans nouvelle table), tableau de bord logistique (commandes actives par étape en temps réel), contrôle qualité par article à réception (conformité/non-conformité, motif obligatoire).
- **Sprint 9** (notifications) : terminé — `NotificationService` central (rendu bilingue dans la langue du destinataire, résolution des canaux par préférence + repli garanti pour les gabarits critiques, historique complet succès/échec par canal), 10 déclencheurs automatiques câblés de bout en bout (confirmation de commande, transitions de statut notifiables, anomalie de contrôle qualité), préférences par canal (push/SMS/e-mail) côté client.
- **Sprint 10** (support & réclamations) : terminé — réclamations rattachées à une commande avec catégorisation et fil de discussion à pièces jointes, cycle de statuts (Ouverte/En cours/En attente client/Résolue/Clôturée) avec prise en charge et retour "en cours" automatiques, centre d'aide (FAQ) public + admin. Point ouvert depuis les Sprints 8-9 refermé : une non-conformité de contrôle qualité ouvre désormais automatiquement une réclamation interne (8.6.1). 238 tests verts au total.
- **Sprint 11** (administration & pilotage) : terminé — gestion des utilisateurs (recherche, blocage temporaire/définitif effectif y compris sur un jeton déjà émis, création de comptes internes), attribution/révocation des rôles internes, journal d'audit sur les actions sensibles (paiement, marge, taux, rôle, blocage), tableau de bord & reporting (CA, panier moyen, marge réalisée, ventilations boutique/zone/produit) avec deux niveaux d'accès et export CSV bilingue réservé au niveau complet. **La gestion des promotions (8.9.5) n'a pas été traitée ce sprint** et reste entièrement à faire. 252 tests verts au total.

Voir `docs/PLAN.md` section 7/7bis/7ter/7quater/7quinquies/7sexies/7septies/7octies/7nonies/7decies/7undecies/7duodecies pour le détail de chaque sprint livré, et section 8 pour les points encore ouverts (granularité fine des permissions, opérateur SMS à choisir, connecteur de synchronisation réel et service de traduction à choisir, vraie intégration Bankily et Firebase Cloud Messaging à obtenir, délais SLA logistiques indicatifs non mesurés et pas encore reliés aux notifications, FAQ réservée à l'administrateur, gestion des promotions non traitée, aucun coût logistique suivi donc pas de marge nette).
