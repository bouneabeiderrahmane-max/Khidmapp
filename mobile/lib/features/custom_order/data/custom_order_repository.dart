import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/network/api_client.dart';
import 'custom_order_models.dart';

class CustomOrderRepository {
  CustomOrderRepository(this._dio);

  final Dio _dio;

  Future<List<CustomOrderRequest>> fetchMyRequests() async {
    final response = await _dio.get('/custom-order-requests');
    return (response.data['data'] as List)
        .map((json) => CustomOrderRequest.fromJson(json as Map<String, dynamic>))
        .toList();
  }

  Future<CustomOrderRequest> fetchRequest(int id) async {
    final response = await _dio.get('/custom-order-requests/$id');
    return CustomOrderRequest.fromJson(response.data['data'] as Map<String, dynamic>);
  }

  Future<CustomOrderRequest> submit({
    required int addressId,
    required String paymentMethod,
    required List<NewCustomOrderItem> items,
  }) async {
    final response = await _dio.post(
      '/custom-order-requests',
      data: {
        'address_id': addressId,
        'payment_method': paymentMethod,
        'items': items.map((i) => i.toJson()).toList(),
      },
    );
    return CustomOrderRequest.fromJson(response.data['data'] as Map<String, dynamic>);
  }

  /// Aperçu instantané du prix en MRU (marge incluse) pour un prix EUR que
  /// le client vient de lire sur le vrai site externe d'une boutique — le
  /// client ne doit jamais voir un prix EUR seul, sans son équivalent en
  /// MRU (règle transverse). Endpoint public, aucun état créé.
  Future<double> previewPriceMru({required double priceEur, int? boutiqueId}) async {
    final response = await _dio.get(
      '/custom-order-price-preview',
      queryParameters: {'price_eur': priceEur, if (boutiqueId != null) 'boutique_id': boutiqueId},
    );
    return num.parse(response.data['data']['subtotal_mru'].toString()).toDouble();
  }
}

final customOrderRepositoryProvider = Provider<CustomOrderRepository>((ref) {
  return CustomOrderRepository(ref.watch(apiClientProvider).dio);
});
