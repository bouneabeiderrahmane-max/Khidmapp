import 'package:dio/dio.dart';

/// Message d'erreur lisible extrait d'une réponse API (ou message
/// générique si la réponse n'a pas la forme attendue, ex. hors-ligne).
String apiErrorMessage(Object error, {String fallback = 'Une erreur est survenue.'}) {
  if (error is DioException) {
    final data = error.response?.data;
    if (data is Map && data['message'] is String) {
      return data['message'] as String;
    }
    if (data is Map && data['errors'] is Map) {
      final errors = (data['errors'] as Map).values.expand((v) => v is List ? v : [v]);
      if (errors.isNotEmpty) return errors.first.toString();
    }
  }
  return fallback;
}
