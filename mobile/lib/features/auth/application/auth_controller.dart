import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/network/api_client.dart';
import '../data/auth_repository.dart';

/// `null` = non authentifié. La reconstruction au démarrage relit le
/// jeton en stockage sécurisé et valide la session via `GET /me` (un
/// jeton expiré/invalide échoue silencieusement vers "non authentifié",
/// cohérent avec l'intercepteur qui purge déjà le jeton sur 401 côté API).
class AuthController extends AsyncNotifier<AuthUser?> {
  @override
  Future<AuthUser?> build() async {
    final storage = ref.watch(secureStorageProvider);
    final token = await storage.read(key: tokenStorageKey);
    if (token == null) return null;

    try {
      return await ref.watch(authRepositoryProvider).me();
    } catch (_) {
      await storage.delete(key: tokenStorageKey);
      return null;
    }
  }

  Future<void> requestOtp(String phone) {
    return ref.read(authRepositoryProvider).requestOtp(phone);
  }

  Future<void> verifyOtp({
    required String phone,
    required String code,
    String? name,
  }) async {
    final session = await ref
        .read(authRepositoryProvider)
        .verifyOtp(phone: phone, code: code, name: name);
    await ref.read(secureStorageProvider).write(key: tokenStorageKey, value: session.token);
    state = AsyncData(session.user);
  }

  Future<void> logout() async {
    await ref.read(secureStorageProvider).delete(key: tokenStorageKey);
    state = const AsyncData(null);
  }
}

final authControllerProvider = AsyncNotifierProvider<AuthController, AuthUser?>(
  AuthController.new,
);

final isAuthenticatedProvider = Provider<bool>((ref) {
  return ref.watch(authControllerProvider).valueOrNull != null;
});
