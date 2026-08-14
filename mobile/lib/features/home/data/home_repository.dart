import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/network/api_client.dart';

class BoutiqueSummary {
  const BoutiqueSummary({
    required this.id,
    required this.name,
    required this.slug,
    this.logoUrl,
    this.countryCode,
  });

  factory BoutiqueSummary.fromJson(Map<String, dynamic> json) => BoutiqueSummary(
    id: json['id'] as int,
    name: json['name'] as String,
    slug: json['slug'] as String,
    logoUrl: json['logo_url'] as String?,
    countryCode: json['country_code'] as String?,
  );

  final int id;
  final String name;
  final String slug;
  final String? logoUrl;
  final String? countryCode;
}

class HomeRepository {
  HomeRepository(this._dio);

  final Dio _dio;

  /// Catalogue public des boutiques actives (déjà filtré côté API,
  /// voir BoutiqueController::index) — pas de pagination gérée côté
  /// mobile pour l'instant, le nombre de boutiques reste faible.
  Future<List<BoutiqueSummary>> fetchBoutiques() async {
    final response = await _dio.get('/boutiques', queryParameters: {'per_page': 100});
    return (response.data['data'] as List)
        .map((json) => BoutiqueSummary.fromJson(json as Map<String, dynamic>))
        .toList();
  }
}

final homeRepositoryProvider = Provider<HomeRepository>((ref) {
  return HomeRepository(ref.watch(apiClientProvider).dio);
});
