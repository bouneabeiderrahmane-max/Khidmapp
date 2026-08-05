import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:khidmapp/app.dart';
import 'package:khidmapp/features/catalog/data/catalog_models.dart';
import 'package:khidmapp/features/catalog/data/catalog_repository.dart';

/// Aucune requête réseau réelle en test : le catalogue affiché à l'écran de
/// démarrage dépend de `catalogRepositoryProvider`, remplacé ici par un
/// faux dépôt qui répond immédiatement (sinon `pumpAndSettle` n'arrête
/// jamais d'attendre un indicateur de chargement qui ne se résout pas).
class _FakeCatalogRepository implements CatalogRepository {
  @override
  Future<List<Category>> fetchCategories() async => [];

  @override
  Future<List<ProductSummary>> searchProducts(CatalogFilters filters) async => [];

  @override
  Future<ProductDetail> fetchProductDetail(int id) {
    throw UnimplementedError();
  }
}

void main() {
  testWidgets('App builds and shows the catalog screen', (tester) async {
    await tester.pumpWidget(
      ProviderScope(
        overrides: [catalogRepositoryProvider.overrideWithValue(_FakeCatalogRepository())],
        child: const KhidmappApp(),
      ),
    );
    await tester.pumpAndSettle();

    expect(find.text('Catalogue'), findsWidgets);
  });
}
