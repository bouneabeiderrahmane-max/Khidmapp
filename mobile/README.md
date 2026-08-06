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

L'URL de l'API par défaut (`lib/core/config/env.dart`) pointe vers l'IP Wi-Fi locale du poste de développement, pour tester depuis un téléphone physique sur le même réseau. Elle est configurable au lancement, à surcharger selon la cible :

```bash
# Émulateur Android (alias spécial vers le localhost de l'hôte)
flutter run --dart-define=API_BASE_URL=http://10.0.2.2:8000/api/v1

# Simulateur iOS (le localhost de l'hôte y est directement joignable)
flutter run --dart-define=API_BASE_URL=http://127.0.0.1:8000/api/v1

# Téléphone physique sur le même Wi-Fi que le poste de développement
flutter run --dart-define=API_BASE_URL=http://<IP_DU_POSTE>:8000/api/v1
```

**Android — trafic HTTP en clair (dev local uniquement)** : depuis l'API 28, Android bloque par défaut le cleartext (HTTP non chiffré), ce qu'utilise forcément un backend local sans certificat. `android/app/src/debug/res/xml/network_security_config.xml` l'autorise, mais **seulement** vers l'IP en dur dans `env.dart` et **seulement** pour le build debug (jamais release) — si cette IP change, mettre à jour les deux fichiers ensemble.

## Ce qui est connecté à l'API réelle

- **Authentification** (`/login`) : connexion par OTP téléphone (`POST /auth/otp/request` puis `/auth/otp/verify`), jeton JWT en stockage sécurisé (`flutter_secure_storage`), auto-inscription au premier code vérifié (comportement de l'API).
- **Catalogue** (`/catalog`) : recherche (`GET /products?q=...`), filtre par catégorie (`GET /categories`), tri (nouveautés/prix). Grille de produits avec prix final en MRU, jamais de prix EUR/URL boutique — l'API ne les expose de toute façon pas au client.
- **Fiche produit** (`/product/:id`) : `GET /products/{id}`, sélecteur taille/couleur par variante, ajout au panier (`POST /cart/items`) — demande une connexion si l'utilisateur n'est pas authentifié.
- **Compte** (`/account`) : profil (`GET /me`), adresses (liste/ajout/suppression via `/addresses`), préférences de notification par canal push/SMS/e-mail (`/notification-preferences`), déconnexion. Invite à se connecter si non authentifié.
- **Support** (`/support`) : FAQ publique (`GET /faqs`, bilingue, accessible sans connexion), mes réclamations (`GET /complaints`), nouvelle réclamation liée à une commande (`POST /complaints`), fil de discussion avec réponse (`POST /complaints/{id}/messages`).

Vérifié uniquement via `flutter analyze`/`flutter test` et une comparaison directe des réponses JSON réelles de l'API (`curl`, flux complet rejoué : adresse → commande → réclamation → réponse) avec les modèles Dart — **aucune vérification visuelle sur émulateur/appareil n'a été possible** dans cette session (voir la limite Android SDK ci-dessous). À tester sur un appareil réel avant de considérer ces écrans terminés.

## Limites connues — signalées explicitement

- **Panier et Commandes restent des écrans de substitution** — Catalogue, fiche produit, Compte et Support sont maintenant connectés ; Panier (vue dédiée, indépendante de l'ajout depuis la fiche produit) et Commandes (liste/suivi) restent à faire.
- **Pas de garde de connexion globale** : le catalogue et la FAQ restent consultables sans compte (cohérent avec l'API publique) ; les actions qui en ont besoin (ajouter au panier, ouvrir une réclamation, voir ses réclamations) redirigent vers `/login` ou invitent à se connecter au niveau de l'écran, pas via une redirection globale.
- **Compte** : édition du profil (nom/e-mail/téléphone), modification d'une adresse existante (seuls l'ajout et la suppression sont câblés) et désactivation de compte ne sont pas construits.
- **Réclamations sans pièce jointe** : l'API accepte des images en pièce jointe (`multipart/form-data`) ; l'écran mobile n'envoie que le texte du message pour l'instant.
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
