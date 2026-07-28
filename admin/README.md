# Khidmapp Admin

Interface d'administration (React + TypeScript + Vite) pour l'API Khidmapp — voir [`../docs/PLAN.md`](../docs/PLAN.md) pour l'architecture complète et le découpage en sprints.

## Démarrer

```bash
npm install
npm run dev
```

Par défaut, l'app appelle `http://localhost:8000/api/v1` (le port par défaut de `php artisan serve`). Pour pointer vers une autre URL, définir `VITE_API_BASE_URL` dans un fichier `.env.local`.

## Ce qui est connecté à l'API réelle

- **Authentification** (`/login`) : connexion e-mail/mot de passe (`POST /auth/login`), jeton JWT en `localStorage`, déconnexion automatique sur 401 (jeton expiré/invalide), navigation adaptée au rôle connecté (`GET /me`).
- **Tableau de bord** (`/`) : `GET /admin/dashboard/report` avec filtres période/boutique/zone ; les chiffres financiers (CA, panier moyen, marge) ne s'affichent que pour le niveau d'accès complet (`dashboard.view_full`, administrateur) — le service client (`dashboard.view_limited`) voit les mêmes compteurs opérationnels sans les montants, exactement comme l'API les renvoie. Export CSV réservé au niveau complet.
- **Boutiques** (`/boutiques`, administrateur uniquement) : liste, création, changement de statut, suppression.
- **Commandes** (`/orders`, `/orders/:id`) : liste filtrable par statut, détail (articles, paiements, historique complet des statuts), changement de statut — la validation de la transition reste entièrement côté API, l'admin ne fait que relayer l'erreur 422 si la transition est refusée.
- **Paiements manuels** (`/payments`) : file d'attente filtrable par statut, validation/refus (motif obligatoire)/demande de complément, lien vers la preuve.
- **Utilisateurs** (`/users`, administrateur uniquement) : recherche, filtre par rôle, blocage (motif obligatoire)/déblocage.
- **Rôles et permissions** (`/roles`, administrateur uniquement) : création de compte interne, remplacement du rôle d'un compte existant.
- **Rapports** (`/reports`, administrateur uniquement) : journal d'audit filtrable par action.

## Limites connues — signalées explicitement

- **Bilinguisme incomplet** : le squelette du Sprint 0 (navigation, en-tête, écran de connexion) est bien traduit FR/AR avec RTL fonctionnel, mais le contenu des six pages connectées à l'API dans cette itération (libellés de tableau, formulaires, messages) est actuellement rédigé en français en dur, pas via `i18next`. Passer en arabe change la mise en page en RTL mais pas le texte de ces pages. À corriger avant mise en production.
- **Panier/catalogue client** non couverts ici : cette itération porte sur l'usage interne (service client/administrateur), pas sur une éventuelle interface de vente. Le mobile Flutter reste le canal client prévu.
- **Commande manuelle** (création d'une commande pour le compte d'un client, 8.4.2) non construite côté admin : l'endpoint existe (`POST /admin/orders`) mais nécessite un sélecteur d'articles non encore développé.
- **Gestion des promotions (8.9.5)** toujours hors périmètre — aucun endpoint n'existe côté API non plus (voir `docs/PLAN.md` §8).

## Tests

```bash
npm run lint
npx tsc -b
npm run build
```

Aucun test automatisé (unitaire/E2E) n'existe encore pour cette app — la vérification de ce sprint a été faite manuellement en navigateur (Playwright, captures d'écran) contre l'API réelle, pas par une suite de tests committée.
