import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:khidmapp/app.dart';
import 'package:khidmapp/features/catalog/data/catalog_models.dart';
import 'package:khidmapp/features/catalog/data/catalog_repository.dart';
import 'package:khidmapp/features/home/data/home_repository.dart';

/// Aucune requête réseau réelle en test : l'écran de démarrage (Accueil)
/// dépend de `homeRepositoryProvider` (liste des boutiques) et l'onglet
/// Catalogue de `catalogRepositoryProvider` — tous deux remplacés ici par
/// de faux dépôts qui répondent immédiatement (sinon `pumpAndSettle`
/// n'arrête jamais d'attendre un indicateur de chargement qui ne se
/// résout pas).
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

class _FakeHomeRepository implements HomeRepository {
  @override
  Future<List<BoutiqueSummary>> fetchBoutiques() async => [];
}

void main() {
  testWidgets('App builds and shows the home screen', (tester) async {
    await tester.pumpWidget(
      ProviderScope(
        overrides: [
          catalogRepositoryProvider.overrideWithValue(_FakeCatalogRepository()),
          homeRepositoryProvider.overrideWithValue(_FakeHomeRepository()),
        ],
        child: const KhidmappApp(),
      ),
    );
    await tester.pumpAndSettle();

    expect(find.text('Accueil'), findsWidgets);
  });
}
