import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/network/api_client.dart';
import 'cart_models.dart';

class CartRepository {
  CartRepository(this._dio);

  final Dio _dio;

  Future<CartSummary> fetchCart() async {
    final response = await _dio.get('/cart');
    return CartSummary.fromJson(response.data['data'] as Map<String, dynamic>);
  }

  Future<CartSummary> addItem({required int productVariantId, int quantity = 1}) async {
    final response = await _dio.post(
      '/cart/items',
      data: {'product_variant_id': productVariantId, 'quantity': quantity},
    );
    return CartSummary.fromJson(response.data['data'] as Map<String, dynamic>);
  }

  Future<CartSummary> updateItemQuantity(int cartItemId, int quantity) async {
    final response = await _dio.put('/cart/items/$cartItemId', data: {'quantity': quantity});
    return CartSummary.fromJson(response.data['data'] as Map<String, dynamic>);
  }

  Future<CartSummary> removeItem(int cartItemId) async {
    final response = await _dio.delete('/cart/items/$cartItemId');
    return CartSummary.fromJson(response.data['data'] as Map<String, dynamic>);
  }
}

final cartRepositoryProvider = Provider<CartRepository>((ref) {
  return CartRepository(ref.watch(apiClientProvider).dio);
});
