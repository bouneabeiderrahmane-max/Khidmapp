import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/network/api_client.dart';

/// Dépôt minimal — seul l'ajout au panier est nécessaire tant que l'écran
/// Panier lui-même reste à construire (voir docs/PLAN.md, module Catalogue
/// traité en priorité).
class CartRepository {
  CartRepository(this._dio);

  final Dio _dio;

  Future<void> addItem({required int productVariantId, int quantity = 1}) {
    return _dio.post(
      '/cart/items',
      data: {'product_variant_id': productVariantId, 'quantity': quantity},
    );
  }
}

final cartRepositoryProvider = Provider<CartRepository>((ref) {
  return CartRepository(ref.watch(apiClientProvider).dio);
});
