import 'package:flutter/foundation.dart' show kDebugMode;
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'env.dart';

const apiBaseUrlOverrideStorageKey = 'dev_api_base_url_override';

/// URL de base de l'API effectivement utilisée par [ApiClient] — modifiable
/// à chaud depuis l'écran "Paramètres développeur" (debug uniquement, voir
/// [DevSettingsPage]) sans reconstruire l'APK à chaque changement d'IP
/// locale. Valeur initiale chargée de façon synchrone au démarrage (voir
/// [loadInitialApiBaseUrl] dans main.dart) pour que ce provider — et donc
/// apiClientProvider qui en dépend — reste un simple Provider synchrone.
final apiBaseUrlProvider = StateProvider<String>((ref) => Env.apiBaseUrl);

/// Relit la préférence enregistrée en stockage sécurisé avant `runApp()`.
/// Ignorée en release : le champ de réglage lui-même n'y est pas exposé
/// (voir DevSettingsPage), donc rien n'est jamais écrit sous cette clé
/// dans un build release — cette relecture reste inoffensive par défaut.
Future<String> loadInitialApiBaseUrl() async {
  if (!kDebugMode) return Env.apiBaseUrl;

  final stored = await const FlutterSecureStorage().read(key: apiBaseUrlOverrideStorageKey);
  return (stored != null && stored.trim().isNotEmpty) ? stored.trim() : Env.apiBaseUrl;
}

class DevSettingsRepository {
  const DevSettingsRepository(this._storage);

  final FlutterSecureStorage _storage;

  Future<void> saveApiBaseUrl(String url) {
    return _storage.write(key: apiBaseUrlOverrideStorageKey, value: url);
  }

  Future<void> clearApiBaseUrl() {
    return _storage.delete(key: apiBaseUrlOverrideStorageKey);
  }
}

final devSettingsRepositoryProvider = Provider<DevSettingsRepository>((ref) {
  return const DevSettingsRepository(FlutterSecureStorage());
});
