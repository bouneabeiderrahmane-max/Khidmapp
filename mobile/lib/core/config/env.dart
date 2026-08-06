class Env {
  // IP locale du poste de développement (Wi-Fi), pas 10.0.2.2 (alias
  // émulateur Android uniquement) : nécessaire pour tester depuis un
  // téléphone physique sur le même réseau. À adapter si l'IP change
  // (ou surcharger via --dart-define=API_BASE_URL=...).
  static const apiBaseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'http://192.168.100.194:8000/api/v1',
  );
}
