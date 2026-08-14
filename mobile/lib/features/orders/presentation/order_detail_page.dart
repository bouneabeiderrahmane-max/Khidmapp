import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/network/api_exception.dart';
import '../../../l10n/generated/app_localizations.dart';
import '../application/order_providers.dart';
import '../data/order_summary_repository.dart';

class OrderDetailPage extends ConsumerWidget {
  const OrderDetailPage({super.key, required this.orderId});

  final int orderId;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    final locale = Localizations.localeOf(context).languageCode;
    final orderAsync = ref.watch(orderDetailProvider(orderId));

    return Scaffold(
      appBar: AppBar(title: Text('#$orderId')),
      body: orderAsync.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (error, _) => Center(child: Text(apiErrorMessage(error))),
        data: (order) => ListView(
          padding: const EdgeInsets.all(16),
          children: [
            Text(order.statusLabel, style: Theme.of(context).textTheme.titleLarge),
            const SizedBox(height: 4),
            Text(
              '${order.shipping.label} — ${order.shipping.city}'
              '${order.shipping.area != null ? ', ${order.shipping.area}' : ''}',
              style: Theme.of(context).textTheme.bodySmall?.copyWith(
                color: Theme.of(context).colorScheme.outline,
              ),
            ),
            const SizedBox(height: 16),
            Text(l10n.orderItemsTitle, style: Theme.of(context).textTheme.titleSmall),
            const SizedBox(height: 8),
            for (final item in order.items)
              Card(
                margin: const EdgeInsets.only(bottom: 8),
                child: ListTile(
                  title: Text(item.productName.forLocale(locale)),
                  subtitle: Text(
                    [item.size, item.color].whereType<String>().join(' / '),
                  ),
                  trailing: Text('×${item.quantity} · ${item.lineSubtotalMru.toStringAsFixed(2)} MRU'),
                ),
              ),
            const SizedBox(height: 8),
            Card(
              child: Padding(
                padding: const EdgeInsets.all(12),
                child: Column(
                  children: [
                    _TotalRow(label: l10n.cartSubtotal, value: order.subtotalMru),
                    _TotalRow(label: l10n.cartDeliveryFee, value: order.deliveryFeeMru),
                    const Divider(),
                    _TotalRow(label: l10n.cartTotal, value: order.totalMru, emphasize: true),
                  ],
                ),
              ),
            ),
            if (order.payments.isNotEmpty) ...[
              const SizedBox(height: 16),
              Text(l10n.orderPaymentsTitle, style: Theme.of(context).textTheme.titleSmall),
              const SizedBox(height: 8),
              for (final payment in order.payments)
                Card(
                  margin: const EdgeInsets.only(bottom: 8),
                  child: ListTile(
                    title: Text(payment.methodLabel),
                    subtitle: Text(payment.statusLabel),
                    trailing: Text('${payment.amountMru.toStringAsFixed(2)} MRU'),
                  ),
                ),
            ],
            const SizedBox(height: 16),
            Text(l10n.orderStatusHistoryTitle, style: Theme.of(context).textTheme.titleSmall),
            const SizedBox(height: 8),
            for (final entry in order.statusHistory)
              ListTile(
                dense: true,
                contentPadding: EdgeInsets.zero,
                title: Text(entry.toStatusLabel),
                subtitle: entry.note != null ? Text(entry.note!) : null,
                trailing: Text(
                  '${entry.createdAt.day}/${entry.createdAt.month}/${entry.createdAt.year}',
                  style: Theme.of(context).textTheme.bodySmall,
                ),
              ),
            if (order.canCancel) ...[
              const SizedBox(height: 20),
              _CancelSection(order: order),
            ],
          ],
        ),
      ),
    );
  }
}

class _TotalRow extends StatelessWidget {
  const _TotalRow({required this.label, required this.value, this.emphasize = false});

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

class _CancelSection extends ConsumerStatefulWidget {
  const _CancelSection({required this.order});

  final OrderDetail order;

  @override
  ConsumerState<_CancelSection> createState() => _CancelSectionState();
}

class _CancelSectionState extends ConsumerState<_CancelSection> {
  final _reasonController = TextEditingController();
  bool _isCancelling = false;
  String? _error;

  @override
  void dispose() {
    _reasonController.dispose();
    super.dispose();
  }

  Future<void> _cancel() async {
    setState(() {
      _isCancelling = true;
      _error = null;
    });
    try {
      await ref
          .read(orderSummaryRepositoryProvider)
          .cancelOrder(widget.order.id, reason: _reasonController.text.trim());
      ref.invalidate(orderDetailProvider(widget.order.id));
      ref.invalidate(myOrdersProvider);
    } catch (e) {
      setState(() => _error = apiErrorMessage(e));
    } finally {
      if (mounted) setState(() => _isCancelling = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            TextField(
              controller: _reasonController,
              decoration: InputDecoration(hintText: l10n.orderCancelReasonHint),
            ),
            if (_error != null) ...[
              const SizedBox(height: 8),
              Text(_error!, style: TextStyle(color: Theme.of(context).colorScheme.error)),
            ],
            const SizedBox(height: 8),
            OutlinedButton(
              onPressed: _isCancelling ? null : _cancel,
              child: Text(l10n.orderCancel),
            ),
          ],
        ),
      ),
    );
  }
}
