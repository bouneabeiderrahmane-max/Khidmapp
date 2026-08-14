class Env {
  // Valeur de repli uniquement : l'IP du poste de développement change à
  // chaque changement de réseau Wi-Fi. En debug, préférer l'écran
  // "Paramètres développeur" (voir DevSettingsPage) qui persiste une
  // valeur modifiable directement depuis le téléphone, sans recompiler.
  // Peut aussi être surchargée au lancement via
  // --dart-define=API_BASE_URL=...
  static const apiBaseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'http://192.168.1.100:8000/api/v1',
  );
}
