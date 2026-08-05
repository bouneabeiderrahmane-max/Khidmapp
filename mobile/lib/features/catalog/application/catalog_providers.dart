import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../data/catalog_models.dart';
import '../data/catalog_repository.dart';

final categoriesProvider = FutureProvider<List<Category>>((ref) {
  return ref.watch(catalogRepositoryProvider).fetchCategories();
});

final catalogFiltersProvider = StateProvider<CatalogFilters>((ref) => const CatalogFilters());

final productsProvider = FutureProvider<List<ProductSummary>>((ref) {
  final filters = ref.watch(catalogFiltersProvider);
  return ref.watch(catalogRepositoryProvider).searchProducts(filters);
});

final productDetailProvider = FutureProvider.family<ProductDetail, int>((ref, id) {
  return ref.watch(catalogRepositoryProvider).fetchProductDetail(id);
});
