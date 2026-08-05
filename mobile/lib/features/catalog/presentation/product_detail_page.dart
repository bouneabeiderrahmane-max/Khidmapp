import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../../core/models/localized_text.dart';
import '../../../core/network/api_exception.dart';
import '../../../l10n/generated/app_localizations.dart';
import '../../auth/application/auth_controller.dart';
import '../../cart/data/cart_repository.dart';
import '../application/catalog_providers.dart';
import '../data/catalog_models.dart';

class ProductDetailPage extends ConsumerStatefulWidget {
  const ProductDetailPage({super.key, required this.productId});

  final int productId;

  @override
  ConsumerState<ProductDetailPage> createState() => _ProductDetailPageState();
}

class _ProductDetailPageState extends ConsumerState<ProductDetailPage> {
  ProductVariantOption? _selectedVariant;
  bool _isAdding = false;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final locale = Localizations.localeOf(context).languageCode;
    final detailAsync = ref.watch(productDetailProvider(widget.productId));

    return Scaffold(
      appBar: AppBar(title: Text(l10n.navCatalog)),
      body: detailAsync.when(
        data: (product) {
          _selectedVariant ??= product.variants.where((v) => v.inStock).firstOrNull ?? product.variants.firstOrNull;

          return SingleChildScrollView(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                AspectRatio(
                  aspectRatio: 1,
                  child: product.images != null && product.images!.isNotEmpty
                      ? Image.network(
                          product.images!.first,
                          fit: BoxFit.cover,
                          errorBuilder: (context, _, __) => _placeholder(context),
                        )
                      : _placeholder(context),
                ),
                Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(product.name.forLocale(locale), style: Theme.of(context).textTheme.headlineSmall),
                      const SizedBox(height: 4),
                      Text(
                        product.boutiqueName,
                        style: Theme.of(
                          context,
                        ).textTheme.bodyMedium?.copyWith(color: Theme.of(context).colorScheme.outline),
                      ),
                      if (product.description != null) ...[
                        const SizedBox(height: 12),
                        Text(product.description!),
                      ],
                      if (product.deliveryEstimate != null) ...[
                        const SizedBox(height: 12),
                        Text(
                          l10n.productDeliveryEstimate(
                            product.deliveryEstimate!.min,
                            product.deliveryEstimate!.max,
                          ),
                          style: Theme.of(context).textTheme.bodySmall,
                        ),
                      ],
                      const SizedBox(height: 16),
                      Text(l10n.productChooseVariant, style: Theme.of(context).textTheme.titleSmall),
                      const SizedBox(height: 8),
                      Wrap(
                        spacing: 8,
                        runSpacing: 8,
                        children: [
                          for (final variant in product.variants)
                            ChoiceChip(
                              label: Text(variant.label.isEmpty ? '#${variant.id}' : variant.label),
                              selected: _selectedVariant?.id == variant.id,
                              onSelected: variant.inStock
                                  ? (_) => setState(() => _selectedVariant = variant)
                                  : null,
                            ),
                        ],
                      ),
                      const SizedBox(height: 20),
                      if (_selectedVariant != null)
                        Text(
                          '${_selectedVariant!.priceMru.toStringAsFixed(2)} MRU',
                          style: Theme.of(context).textTheme.headlineSmall?.copyWith(fontWeight: FontWeight.bold),
                        ),
                      const SizedBox(height: 16),
                      SizedBox(
                        width: double.infinity,
                        child: FilledButton(
                          onPressed: (_selectedVariant == null || _isAdding) ? null : () => _addToCart(context),
                          child: Text(l10n.productAddToCart),
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          );
        },
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (error, _) => Center(child: Text(apiErrorMessage(error))),
      ),
    );
  }

  Widget _placeholder(BuildContext context) {
    return Container(
      color: Theme.of(context).colorScheme.surfaceContainerHighest,
      child: Icon(Icons.storefront_outlined, size: 48, color: Theme.of(context).colorScheme.outline),
    );
  }

  Future<void> _addToCart(BuildContext context) async {
    final l10n = AppLocalizations.of(context);
    final isAuthenticated = ref.read(isAuthenticatedProvider);

    if (!isAuthenticated) {
      final messenger = ScaffoldMessenger.of(context);
      messenger.showSnackBar(SnackBar(content: Text(l10n.loginRequired)));
      await context.push('/login');
      return;
    }

    setState(() => _isAdding = true);
    try {
      await ref.read(cartRepositoryProvider).addItem(productVariantId: _selectedVariant!.id);
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(l10n.productAddedToCart)));
      }
    } catch (e) {
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(apiErrorMessage(e))));
      }
    } finally {
      if (mounted) setState(() => _isAdding = false);
    }
  }
}
