import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../../core/network/api_exception.dart';
import '../../../l10n/generated/app_localizations.dart';
import '../application/catalog_providers.dart';
import '../data/catalog_models.dart';

class CatalogPage extends ConsumerStatefulWidget {
  const CatalogPage({super.key});

  @override
  ConsumerState<CatalogPage> createState() => _CatalogPageState();
}

class _CatalogPageState extends ConsumerState<CatalogPage> {
  final _searchController = TextEditingController();

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final locale = Localizations.localeOf(context).languageCode;
    final filters = ref.watch(catalogFiltersProvider);
    final productsAsync = ref.watch(productsProvider);
    final categoriesAsync = ref.watch(categoriesProvider);

    return Scaffold(
      appBar: AppBar(
        title: Text(l10n.navCatalog),
        actions: [
          PopupMenuButton<String>(
            icon: const Icon(Icons.sort),
            onSelected: (sort) {
              ref.read(catalogFiltersProvider.notifier).state = filters.copyWith(sort: sort, page: 1);
            },
            itemBuilder: (context) => [
              PopupMenuItem(value: 'newest', child: Text(l10n.catalogSortNewest)),
              PopupMenuItem(value: 'price_asc', child: Text(l10n.catalogSortPriceAsc)),
              PopupMenuItem(value: 'price_desc', child: Text(l10n.catalogSortPriceDesc)),
            ],
          ),
        ],
      ),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 8),
            child: TextField(
              controller: _searchController,
              textInputAction: TextInputAction.search,
              decoration: InputDecoration(
                hintText: l10n.catalogSearchHint,
                prefixIcon: const Icon(Icons.search),
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(24)),
                isDense: true,
              ),
              onSubmitted: (value) {
                ref.read(catalogFiltersProvider.notifier).state = filters.copyWith(
                  query: value.trim(),
                  clearQuery: value.trim().isEmpty,
                  page: 1,
                );
              },
            ),
          ),
          categoriesAsync.when(
            data: (categories) => _CategoryChips(categories: categories, locale: locale, filters: filters),
            loading: () => const SizedBox(height: 40),
            error: (_, __) => const SizedBox.shrink(),
          ),
          Expanded(
            child: productsAsync.when(
              data: (products) {
                if (products.isEmpty) {
                  return Center(child: Text(l10n.catalogEmpty));
                }
                return RefreshIndicator(
                  onRefresh: () => ref.refresh(productsProvider.future),
                  child: GridView.builder(
                    padding: const EdgeInsets.all(12),
                    gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                      crossAxisCount: 2,
                      childAspectRatio: 0.68,
                      crossAxisSpacing: 12,
                      mainAxisSpacing: 12,
                    ),
                    itemCount: products.length,
                    itemBuilder: (context, index) => _ProductCard(product: products[index], locale: locale),
                  ),
                );
              },
              loading: () => const Center(child: CircularProgressIndicator()),
              error: (error, _) => Center(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Text(apiErrorMessage(error)),
                    const SizedBox(height: 8),
                    FilledButton(
                      onPressed: () => ref.invalidate(productsProvider),
                      child: Text(l10n.catalogErrorRetry),
                    ),
                  ],
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _CategoryChips extends ConsumerWidget {
  const _CategoryChips({required this.categories, required this.locale, required this.filters});

  final List<Category> categories;
  final String locale;
  final CatalogFilters filters;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);

    return SizedBox(
      height: 40,
      child: ListView(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.symmetric(horizontal: 12),
        children: [
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 4),
            child: ChoiceChip(
              label: Text(l10n.catalogAllCategories),
              selected: filters.categorySlug == null,
              onSelected: (_) {
                ref.read(catalogFiltersProvider.notifier).state = filters.copyWith(clearCategory: true, page: 1);
              },
            ),
          ),
          for (final category in categories)
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 4),
              child: ChoiceChip(
                label: Text(category.name.forLocale(locale)),
                selected: filters.categorySlug == category.slug,
                onSelected: (_) {
                  ref.read(catalogFiltersProvider.notifier).state = filters.copyWith(
                    categorySlug: category.slug,
                    page: 1,
                  );
                },
              ),
            ),
        ],
      ),
    );
  }
}

class _ProductCard extends StatelessWidget {
  const _ProductCard({required this.product, required this.locale});

  final ProductSummary product;
  final String locale;

  @override
  Widget build(BuildContext context) {
    return Card(
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: () => context.push('/product/${product.id}'),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Expanded(
              child: product.image != null
                  ? Image.network(
                      product.image!,
                      fit: BoxFit.cover,
                      errorBuilder: (context, _, __) => const _ImagePlaceholder(),
                    )
                  : const _ImagePlaceholder(),
            ),
            Padding(
              padding: const EdgeInsets.all(8),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    product.name.forLocale(locale),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: Theme.of(context).textTheme.bodyMedium,
                  ),
                  Text(
                    product.boutiqueName,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: Theme.of(
                      context,
                    ).textTheme.bodySmall?.copyWith(color: Theme.of(context).colorScheme.outline),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    '${product.priceFromMru.toStringAsFixed(2)} MRU',
                    style: Theme.of(context).textTheme.titleSmall?.copyWith(fontWeight: FontWeight.bold),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _ImagePlaceholder extends StatelessWidget {
  const _ImagePlaceholder();

  @override
  Widget build(BuildContext context) {
    return Container(
      color: Theme.of(context).colorScheme.surfaceContainerHighest,
      child: Icon(Icons.storefront_outlined, color: Theme.of(context).colorScheme.outline),
    );
  }
}
