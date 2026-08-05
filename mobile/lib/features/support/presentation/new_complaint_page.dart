import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../../core/network/api_exception.dart';
import '../../../l10n/generated/app_localizations.dart';
import '../../orders/data/order_summary_repository.dart';
import '../application/support_providers.dart';
import '../data/support_models.dart';
import '../data/support_repository.dart';

String _categoryLabel(AppLocalizations l10n, String category) {
  return switch (category) {
    'produit_non_conforme' => l10n.complaintCategoryProduitNonConforme,
    'retard' => l10n.complaintCategoryRetard,
    'dommage' => l10n.complaintCategoryDommage,
    'erreur_facturation' => l10n.complaintCategoryErreurFacturation,
    _ => l10n.complaintCategoryAutre,
  };
}

class NewComplaintPage extends ConsumerStatefulWidget {
  const NewComplaintPage({super.key});

  @override
  ConsumerState<NewComplaintPage> createState() => _NewComplaintPageState();
}

class _NewComplaintPageState extends ConsumerState<NewComplaintPage> {
  final _messageController = TextEditingController();
  int? _orderId;
  String _category = complaintCategories.first;
  bool _isSubmitting = false;
  String? _error;

  @override
  void dispose() {
    _messageController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (_orderId == null || _messageController.text.trim().isEmpty) return;

    setState(() {
      _isSubmitting = true;
      _error = null;
    });
    try {
      await ref
          .read(supportRepositoryProvider)
          .createComplaint(orderId: _orderId!, category: _category, message: _messageController.text.trim());
      ref.invalidate(complaintsProvider);
      if (mounted) context.pop();
    } catch (e) {
      setState(() => _error = apiErrorMessage(e));
    } finally {
      if (mounted) setState(() => _isSubmitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final ordersAsync = ref.watch(_myOrdersProvider);

    return Scaffold(
      appBar: AppBar(title: Text(l10n.supportNewComplaint)),
      body: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            ordersAsync.when(
              data: (orders) => DropdownButtonFormField<int>(
                initialValue: _orderId,
                decoration: InputDecoration(labelText: l10n.supportComplaintOrder),
                items: [
                  for (final order in orders)
                    DropdownMenuItem(
                      value: order.id,
                      child: Text('#${order.id} · ${order.statusLabel} · ${order.totalMru.toStringAsFixed(2)} MRU'),
                    ),
                ],
                onChanged: (value) => setState(() => _orderId = value),
              ),
              loading: () => const LinearProgressIndicator(),
              error: (error, _) => Text(apiErrorMessage(error)),
            ),
            const SizedBox(height: 12),
            DropdownButtonFormField<String>(
              initialValue: _category,
              decoration: InputDecoration(labelText: l10n.supportComplaintCategory),
              items: [
                for (final category in complaintCategories)
                  DropdownMenuItem(value: category, child: Text(_categoryLabel(l10n, category))),
              ],
              onChanged: (value) => setState(() => _category = value ?? _category),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: _messageController,
              maxLines: 5,
              decoration: InputDecoration(
                labelText: l10n.supportComplaintMessage,
                border: const OutlineInputBorder(),
              ),
            ),
            if (_error != null) ...[
              const SizedBox(height: 8),
              Text(_error!, style: TextStyle(color: Theme.of(context).colorScheme.error)),
            ],
            const SizedBox(height: 16),
            FilledButton(
              onPressed: (_isSubmitting || _orderId == null) ? null : _submit,
              child: Text(l10n.supportComplaintSend),
            ),
          ],
        ),
      ),
    );
  }
}

final _myOrdersProvider = FutureProvider((ref) {
  return ref.watch(orderSummaryRepositoryProvider).fetchMyOrders();
});
