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
}

final customOrderRepositoryProvider = Provider<CustomOrderRepository>((ref) {
  return CustomOrderRepository(ref.watch(apiClientProvider).dio);
});
