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

L'URL de l'API par défaut (`lib/core/config/env.dart`) est une simple valeur de repli, configurable au lancement (`--dart-define=API_BASE_URL=...`) ou à la compilation. Puisqu'elle change à chaque réseau Wi-Fi lorsqu'on teste depuis un téléphone physique, **le plus simple en debug est de ne pas y toucher et de la changer directement depuis l'application** — voir "Paramètres développeur" ci-dessous.

```bash
# Émulateur Android (alias spécial vers le localhost de l'hôte)
flutter run --dart-define=API_BASE_URL=http://10.0.2.2:8000/api/v1

# Simulateur iOS (le localhost de l'hôte y est directement joignable)
flutter run --dart-define=API_BASE_URL=http://127.0.0.1:8000/api/v1

# Téléphone physique sur le même Wi-Fi que le poste de développement
flutter run --dart-define=API_BASE_URL=http://<IP_DU_POSTE>:8000/api/v1
```

### Paramètres développeur — changer l'URL de l'API depuis le téléphone (debug uniquement)

Plus besoin de modifier le code ni de reconstruire l'APK à chaque changement d'IP : en build **debug** uniquement (absent d'un build release — voir plus bas), l'onglet **Compte** affiche une entrée **"Paramètres développeur"** tout en bas de l'écran (visible connecté ou non). Elle ouvre un formulaire avec :

1. Un champ **"URL de base de l'API"**, pré-rempli avec la valeur actuellement utilisée (ex. `http://192.168.1.100:8000/api/v1`) — remplacez l'IP par celle de votre poste sur le réseau courant (`ipconfig`/`ifconfig`), en gardant `:8000/api/v1` à la fin.
2. **Enregistrer** : la nouvelle URL est aussitôt utilisée par tous les appels API (aucun redémarrage de l'app nécessaire — le client HTTP est reconstruit automatiquement) et persistée en stockage sécurisé (`flutter_secure_storage`), donc conservée après fermeture de l'app.
3. **Réinitialiser à la valeur par défaut** : efface la valeur enregistrée et revient à `Env.apiBaseUrl` (`lib/core/config/env.dart`).

Implémentation : `lib/core/config/dev_settings.dart` (le provider `apiBaseUrlProvider`, dont dépend `apiClientProvider`, réagit au changement comme il réagit déjà à la langue) et `lib/features/dev_settings/`. Cette entrée et cette route ne sont compilées que si `kDebugMode` est vrai — un utilisateur final en build release ne les voit jamais et ne peut pas non plus y accéder par un lien profond, la route elle-même étant absente de la table de routes release.

**Android — trafic HTTP en clair (dev local uniquement)** : depuis l'API 28, Android bloque par défaut le cleartext (HTTP non chiffré), ce qu'utilise forcément un backend local sans certificat. `android/app/src/debug/res/xml/network_security_config.xml` l'autorise pour **tout le trafic**, mais **seulement** pour le build debug (jamais release) — volontairement large plutôt que limité à une IP fixe : Android ne supporte pas les plages CIDR (`192.168.0.0/16`) dans ce fichier, seulement des IP littérales exactes, ce qui aurait de toute façon obligé à modifier ce fichier (et donc reconstruire l'APK) à chaque changement de réseau — exactement ce que "Paramètres développeur" ci-dessus permet d'éviter pour l'URL elle-même.

## Ce qui est connecté à l'API réelle

- **Accueil** (`/`, premier onglet) : accueil nominatif, recherche de boutique, grille des boutiques actives (`GET /boutiques`) — tapoter une boutique ouvre son vrai site dans un **navigateur intégré à l'application** (`BoutiqueWebViewPage`, `webview_flutter`, avec un bouton flottant "Commande personnalisée" toujours accessible) plutôt qu'un catalogue interne ou le navigateur externe du téléphone, puisque Khidmapp ne synchronise pas réellement de catalogue (voir `StubCatalogFetcher`) et que le client doit rester dans l'app. Bouton "Commande personnalisée" vers le formulaire multi-produits (lien produit + quantité + prix affiché + notes par article, adresse/paiement/poids de colis comme un checkout classique, `POST /custom-order-requests`) — le prix EUR saisi est immédiatement doublé d'un aperçu MRU marge incluse (`GET /custom-order-price-preview`, débattu 500 ms après la dernière frappe), pour que le client ne voie jamais un prix EUR seul. Raccourci vers mes demandes (`GET /custom-order-requests`, détail avec traceur des 5 étapes Révision → Achat → Réception/contrôle qualité Madrid → Expédition internationale → Livraison Nouakchott — voir `docs/PLAN.md` §7duodecies ter). Nécessite une connexion et au moins une adresse enregistrée pour soumettre une demande ; le prix final n'est connu qu'après confirmation par l'équipe Khidmapp (statut "En attente de validation" puis "Confirmée"/"Rejetée").
- **Authentification** (`/login`) : connexion par OTP téléphone (`POST /auth/otp/request` puis `/auth/otp/verify`), jeton JWT en stockage sécurisé (`flutter_secure_storage`), auto-inscription au premier code vérifié (comportement de l'API).
- **Catalogue** (`/catalog`) : recherche (`GET /products?q=...`), filtre par catégorie (`GET /categories`), tri (nouveautés/prix). Grille de produits avec prix final en MRU, jamais de prix EUR/URL boutique — l'API ne les expose de toute façon pas au client.
- **Fiche produit** (`/product/:id`) : `GET /products/{id}`, sélecteur taille/couleur par variante, ajout au panier (`POST /cart/items`) — demande une connexion si l'utilisateur n'est pas authentifié.
- **Compte** (`/account`) : profil (`GET /me`), adresses (liste/ajout/suppression via `/addresses`), préférences de notification par canal push/SMS/e-mail (`/notification-preferences`), déconnexion. Invite à se connecter si non authentifié.
- **Support** (`/support`) : FAQ publique (`GET /faqs`, bilingue, accessible sans connexion), mes réclamations (`GET /complaints`), nouvelle réclamation liée à une commande (`POST /complaints`), fil de discussion avec réponse (`POST /complaints/{id}/messages`).
- **Panier** (`/cart`) : contenu réel du panier (`GET /cart`), quantité modifiable/suppression par ligne (`PUT`/`DELETE /cart/items/{id}`), sous-total/frais de livraison/**coût de gestion** (5 %, visible au client — jamais la marge, qui reste cachée dans le prix affiché)/total, bouton vers le checkout.
- **Checkout** (`/checkout`) : trois étapes numérotées façon `Stepper` (Adresse → Mode de livraison → Paiement, sur le modèle d'une app tierce de proxy-achat) — l'étape "Mode de livraison" est le **poids de colis** (`WeightTierSelector` — Petit/Moyen/Très grand paquet, champ de poids supplémentaire optionnel au-delà de 15 kg pour le plus grand palier), l'étape "Paiement" affiche un récapitulatif de coûts (sous-total/livraison/coût de gestion/total) recalculé côté client selon le palier choisi, sans dupliquer le pourcentage de coût de gestion en dur (déduit du panier via `GET /cart`, voir le docblock de `_CheckoutStepper`). Confirmer crée la commande réelle (`POST /orders` — vide le panier côté API), puis initie Bankily si choisi (`POST /orders/{id}/payments/bankily/initiate`, gateway placeholder `StubBankilyGateway` — confirmation réelle async par webhook, non simulable ici).
- **Commandes** (`/orders`, `/orders/:id`) : liste (`GET /orders`), détail (articles, paiements, historique des statuts, même ligne "Coût de gestion" que le panier), auto-annulation dans la fenêtre gratuite (`POST /orders/{id}/cancel`) — la validation de la fenêtre reste entièrement côté API, l'écran relaie tel quel le message d'erreur 422 si le refus est trop tardif.

Vérifié uniquement via `flutter analyze`/`flutter test` et une comparaison directe des réponses JSON réelles de l'API (`curl`, flux complet rejoué : adresse → panier → commande → paiement Bankily → annulation, et adresse → commande → réclamation → réponse) avec les modèles Dart — **aucune vérification visuelle sur émulateur/appareil n'a été possible** dans cette session (voir la limite Android SDK ci-dessous). À tester sur un appareil réel avant de considérer ces écrans terminés.

## Limites connues — signalées explicitement

- **Pas de garde de connexion globale** : le catalogue et la FAQ restent consultables sans compte (cohérent avec l'API publique) ; les actions qui en ont besoin (ajouter au panier, passer commande, ouvrir une réclamation, voir ses réclamations/commandes) redirigent vers `/login` ou invitent à se connecter au niveau de l'écran, pas via une redirection globale.
- **Paiement manuel sans upload de preuve** : le checkout enregistre le choix "virement manuel" et crée la commande, mais l'écran n'envoie pas encore de preuve de paiement (`POST /orders/{id}/payment-proof`, `multipart/form-data`) — même limite que les pièces jointes de réclamation ci-dessous, aucun sélecteur de fichier construit dans cette itération.
- **Paiement Bankily non simulable de bout en bout** : l'initiation crée bien la transaction côté serveur (`StubBankilyGateway`, placeholder — voir son docblock), mais la confirmation arrive par webhook, impossible à déclencher depuis l'app elle-même.
- **Compte** : édition du profil (nom/e-mail/téléphone), modification d'une adresse existante (seuls l'ajout et la suppression sont câblés) et désactivation de compte ne sont pas construits.
- **Réclamations sans pièce jointe** : l'API accepte des images en pièce jointe (`multipart/form-data`) ; l'écran mobile n'envoie que le texte du message pour l'instant.
- **Aucune image produit affichée** dans les données de démonstration actuelles (le champ `image`/`images` de l'API est `null` tant que la synchronisation catalogue reste un simulateur, voir Sprint 3) — un espace réservé (icône) s'affiche à la place, géré proprement, pas une erreur silencieuse.
- **Commande personnalisée** : pas de modification/annulation d'une demande "en attente" une fois soumise côté client ; pas de vérification que l'URL saisie correspond réellement à la boutique optionnellement sélectionnée (choisie seulement pour bénéficier d'une marge boutique plutôt que globale).
- **Aucune conversion de prix sur le vrai site d'une boutique** : même affiché dans le navigateur intégré à l'app, c'est toujours le vrai site du tiers, chargé tel quel — techniquement impossible et trompeur d'y réécrire les prix EUR affichés en MRU (ce serait un prix fabriqué superposé au vrai site d'un tiers). L'aperçu MRU (marge incluse) reste disponible uniquement dans les écrans que Khidmapp contrôle, au moment où le client saisit le prix qu'il vient de lire (formulaire de commande personnalisée).

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
