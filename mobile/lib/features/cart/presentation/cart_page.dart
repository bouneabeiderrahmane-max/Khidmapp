import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../../core/network/api_exception.dart';
import '../../../l10n/generated/app_localizations.dart';
import '../application/cart_providers.dart';
import '../data/cart_models.dart';
import '../data/cart_repository.dart';

class CartPage extends ConsumerWidget {
  const CartPage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    final locale = Localizations.localeOf(context).languageCode;
    final cartAsync = ref.watch(cartProvider);

    return Scaffold(
      appBar: AppBar(title: Text(l10n.navCart)),
      body: cartAsync.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (error, _) => Center(child: Text(apiErrorMessage(error))),
        data: (cart) {
          if (cart.isEmpty) {
            return Center(child: Text(l10n.cartEmpty));
          }

          return Column(
            children: [
              Expanded(
                child: RefreshIndicator(
                  onRefresh: () => ref.refresh(cartProvider.future),
                  child: ListView.builder(
                    padding: const EdgeInsets.all(12),
                    itemCount: cart.items.length,
                    itemBuilder: (context, index) => _CartItemCard(item: cart.items[index], locale: locale),
                  ),
                ),
              ),
              _CartSummaryFooter(cart: cart),
            ],
          );
        },
      ),
    );
  }
}

class _CartItemCard extends ConsumerWidget {
  const _CartItemCard({required this.item, required this.locale});

  final CartItem item;
  final String locale;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);

    Future<void> setQuantity(int quantity) async {
      if (quantity < 1) return;
      await ref.read(cartRepositoryProvider).updateItemQuantity(item.id, quantity);
      ref.invalidate(cartProvider);
    }

    Future<void> remove() async {
      await ref.read(cartRepositoryProvider).removeItem(item.id);
      ref.invalidate(cartProvider);
    }

    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      child: Padding(
        padding: const EdgeInsets.all(8),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            ClipRRect(
              borderRadius: BorderRadius.circular(8),
              child: SizedBox(
                width: 64,
                height: 64,
                child: item.image != null
                    ? Image.network(
                        item.image!,
                        fit: BoxFit.cover,
                        errorBuilder: (context, _, __) => const _ImagePlaceholder(),
                      )
                    : const _ImagePlaceholder(),
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(item.productName.forLocale(locale), maxLines: 2, overflow: TextOverflow.ellipsis),
                  Text(
                    item.boutiqueName,
                    style: Theme.of(
                      context,
                    ).textTheme.bodySmall?.copyWith(color: Theme.of(context).colorScheme.outline),
                  ),
                  if (item.size != null || item.color != null)
                    Text(
                      [item.size, item.color].whereType<String>().join(' / '),
                      style: Theme.of(context).textTheme.bodySmall,
                    ),
                  const SizedBox(height: 4),
                  Row(
                    children: [
                      IconButton(
                        icon: const Icon(Icons.remove_circle_outline),
                        visualDensity: VisualDensity.compact,
                        onPressed: () => setQuantity(item.quantity - 1),
                      ),
                      Text('${item.quantity}'),
                      IconButton(
                        icon: const Icon(Icons.add_circle_outline),
                        visualDensity: VisualDensity.compact,
                        onPressed: () => setQuantity(item.quantity + 1),
                      ),
                      const Spacer(),
                      Text(
                        '${item.lineSubtotalMru.toStringAsFixed(2)} MRU',
                        style: Theme.of(context).textTheme.titleSmall,
                      ),
                    ],
                  ),
                ],
              ),
            ),
            IconButton(
              icon: const Icon(Icons.delete_outline),
              tooltip: l10n.cartRemove,
              onPressed: remove,
            ),
          ],
        ),
      ),
    );
  }
}

class _CartSummaryFooter extends StatelessWidget {
  const _CartSummaryFooter({required this.cart});

  final CartSummary cart;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);

    return SafeArea(
      child: Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: Theme.of(context).colorScheme.surface,
          border: Border(top: BorderSide(color: Theme.of(context).colorScheme.outlineVariant)),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            _SummaryRow(label: l10n.cartSubtotal, value: cart.subtotalMru),
            _SummaryRow(label: l10n.cartDeliveryFee, value: cart.deliveryFeeMru),
            const Divider(),
            _SummaryRow(label: l10n.cartTotal, value: cart.totalMru, emphasize: true),
            const SizedBox(height: 12),
            FilledButton(
              onPressed: () => context.push('/checkout'),
              child: Text(l10n.cartCheckout),
            ),
          ],
        ),
      ),
    );
  }
}

class _SummaryRow extends StatelessWidget {
  const _SummaryRow({required this.label, required this.value, this.emphasize = false});

  final String label;
  final double value;
  final bool emphasize;

  @override
  Widget build(BuildContext context) {
    final style = emphasize
        ? Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.bold)
        : Theme.of(context).textTheme.bodyMedium;

    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 2),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: style),
          Text('${value.toStringAsFixed(2)} MRU', style: style),
        ],
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
      child: Icon(Icons.shopping_bag_outlined, color: Theme.of(context).colorScheme.outline),
    );
  }
}
