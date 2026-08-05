import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import '../config/env.dart';
import '../config/locale_provider.dart';

const tokenStorageKey = 'access_token';

class ApiClient {
  ApiClient({String? locale}) : _locale = locale ?? 'fr' {
    dio = Dio(BaseOptions(baseUrl: Env.apiBaseUrl));
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

/// Reconstruit le client (donc l'en-tête Accept-Language) quand la langue
/// change ; la locale change rarement, le coût de recréer Dio est
/// négligeable.
final apiClientProvider = Provider<ApiClient>((ref) {
  final locale = ref.watch(localeProvider);
  return ApiClient(locale: locale.languageCode);
});

final secureStorageProvider = Provider<FlutterSecureStorage>((ref) {
  return const FlutterSecureStorage();
});
