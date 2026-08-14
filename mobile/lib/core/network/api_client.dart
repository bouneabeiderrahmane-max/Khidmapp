import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import '../config/dev_settings.dart';
import '../config/locale_provider.dart';

const tokenStorageKey = 'access_token';

class ApiClient {
  ApiClient({required String baseUrl, String? locale}) : _locale = locale ?? 'fr' {
    dio = Dio(BaseOptions(baseUrl: baseUrl));
    dio.interceptors.add(
      InterceptorsWrapper(
        onRequest: (options, handler) async {
          options.headers['Accept-Language'] = _locale;
          final token = await _storage.read(key: tokenStorageKey);
          if (token != null) {
            options.headers['Authorization'] = 'Bearer $token';
          }
          handler.next(options);
        },
      ),
    );
  }

  late final Dio dio;
  final String _locale;
  final _storage = const FlutterSecureStorage();
}

/// Reconstruit le client (donc l'en-tête Accept-Language, ou l'URL de base
/// si modifiée depuis les Paramètres développeur — voir dev_settings.dart)
/// quand l'un ou l'autre change ; les deux changent rarement, le coût de
/// recréer Dio est négligeable.
final apiClientProvider = Provider<ApiClient>((ref) {
  final locale = ref.watch(localeProvider);
  final baseUrl = ref.watch(apiBaseUrlProvider);
  return ApiClient(locale: locale.languageCode, baseUrl: baseUrl);
});

final secureStorageProvider = Provider<FlutterSecureStorage>((ref) {
  return const FlutterSecureStorage();
});
