# Khidmapp — Application mobile (Flutter)

Client mobile Khidmapp (Android + iOS), bilingue français/arabe avec support RTL complet.

## Prérequis

- Flutter 3.44+ (canal stable)
- Un backend Khidmapp lancé localement (voir `../backend`)

## Démarrer

```bash
flutter pub get
flutter gen-l10n   # régénère lib/l10n/generated à partir des fichiers ARB
flutter run
```

L'URL de l'API est configurable au lancement :

```bash
flutter run --dart-define=API_BASE_URL=http://10.0.2.2:8000/api/v1
```

## Ce qui est connecté à l'API réelle

- **Authentification** (`/login`) : connexion par OTP téléphone (`POST /auth/otp/request` puis `/auth/otp/verify`), jeton JWT en stockage sécurisé (`flutter_secure_storage`), auto-inscription au premier code vérifié (comportement de l'API).
- **Catalogue** (`/catalog`) : recherche (`GET /products?q=...`), filtre par catégorie (`GET /categories`), tri (nouveautés/prix). Grille de produits avec prix final en MRU, jamais de prix EUR/URL boutique — l'API ne les expose de toute façon pas au client.
- **Fiche produit** (`/product/:id`) : `GET /products/{id}`, sélecteur taille/couleur par variante, ajout au panier (`POST /cart/items`) — demande une connexion si l'utilisateur n'est pas authentifié.

Vérifié uniquement via `flutter analyze`/`flutter test` et une comparaison directe des réponses JSON réelles de l'API (`curl`) avec les modèles Dart — **aucune vérification visuelle sur émulateur/appareil n'a été possible** dans cette session (voir la limite Android SDK ci-dessous). À tester sur un appareil réel avant de considérer ces écrans terminés.

## Limites connues — signalées explicitement

- **Panier, Commandes, Support, Compte restent des écrans de substitution** — seul le Catalogue (+ fiche produit + ajout au panier) a été connecté dans cette itération, à la demande explicite ("commencer par le Catalogue").
- **Pas de garde de connexion globale** : le catalogue reste consultable sans compte (cohérent avec l'API publique) ; seule l'action "Ajouter au panier" redirige vers `/login`. Les onglets Panier/Commandes/Compte n'ont pas encore leur propre garde puisqu'ils sont toujours des écrans de substitution.
- **Aucune image produit affichée** dans les données de démonstration actuelles (le champ `image`/`images` de l'API est `null` tant que la synchronisation catalogue reste un simulateur, voir Sprint 3) — un espace réservé (icône) s'affiche à la place, géré proprement, pas une erreur silencieuse.

## Structure

```
lib/
├── core/         # config, client HTTP, thème
├── features/     # écrans par fonctionnalité (un dossier par sprint/module)
├── l10n/         # fichiers de traduction ARB (fr, ar) + classes générées
├── routing/      # configuration go_router
├── app.dart      # widget racine (MaterialApp.router, localisation)
└── main.dart
```

## Tests

```bash
flutter analyze
flutter test
```
