import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/network/api_client.dart';
import 'catalog_models.dart';

class CatalogRepository {
  CatalogRepository(this._dio);

  final Dio _dio;

  Future<List<Category>> fetchCategories() async {
    final response = await _dio.get('/categories');
    return (response.data['data'] as List)
        .map((json) => Category.fromJson(json as Map<String, dynamic>))
        .toList();
  }

  Future<List<ProductSummary>> searchProducts(CatalogFilters filters) async {
    final response = await _dio.get('/products', queryParameters: filters.toQueryParams());
    return (response.data['data'] as List)
        .map((json) => ProductSummary.fromJson(json as Map<String, dynamic>))
        .toList();
  }

  Future<ProductDetail> fetchProductDetail(int id) async {
    final response = await _dio.get('/products/$id');
    return ProductDetail.fromJson(response.data['data'] as Map<String, dynamic>);
  }
}

final catalogRepositoryProvider = Provider<CatalogRepository>((ref) {
  return CatalogRepository(ref.watch(apiClientProvider).dio);
});
