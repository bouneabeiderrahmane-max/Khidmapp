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
