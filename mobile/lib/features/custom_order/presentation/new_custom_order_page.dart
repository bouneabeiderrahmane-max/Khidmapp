import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../../core/network/api_exception.dart';
import '../../../l10n/generated/app_localizations.dart';
import '../../account/application/account_providers.dart';
import '../../auth/application/auth_controller.dart';
import '../../home/application/home_providers.dart';
import '../../home/data/home_repository.dart';
import '../application/custom_order_providers.dart';
import '../data/custom_order_models.dart';
import '../data/custom_order_repository.dart';

class _ItemFormState {
  _ItemFormState()
    : productUrlController = TextEditingController(),
      quantityController = TextEditingController(text: '1'),
      priceController = TextEditingController(),
      notesController = TextEditingController();

  final TextEditingController productUrlController;
  final TextEditingController quantityController;
  final TextEditingController priceController;
  final TextEditingController notesController;
  int? boutiqueId;

  void dispose() {
    productUrlController.dispose();
    quantityController.dispose();
    priceController.dispose();
    notesController.dispose();
  }
}

class NewCustomOrderPage extends ConsumerStatefulWidget {
  const NewCustomOrderPage({super.key});

  @override
  ConsumerState<NewCustomOrderPage> createState() => _NewCustomOrderPageState();
}

class _NewCustomOrderPageState extends ConsumerState<NewCustomOrderPage> {
  final List<_ItemFormState> _items = [_ItemFormState()];
  int? _addressId;
  String _paymentMethod = 'bankily';
  bool _isSubmitting = false;
  String? _error;

  @override
  void dispose() {
    for (final item in _items) {
      item.dispose();
    }
    super.dispose();
  }

  bool get _canSubmit =>
      !_isSubmitting &&
      _addressId != null &&
      _items.every(
        (i) => i.productUrlController.text.trim().isNotEmpty && i.priceController.text.trim().isNotEmpty,
      );

  Future<void> _submit() async {
    setState(() {
      _isSubmitting = true;
      _error = null;
    });
    try {
      final items = _items
          .map(
            (i) => NewCustomOrderItem(
              boutiqueId: i.boutiqueId,
              productUrl: i.productUrlController.text.trim(),
              quantity: int.tryParse(i.quantityController.text.trim()) ?? 1,
              estimatedPriceEur: double.parse(i.priceController.text.trim().replaceAll(',', '.')),
              notes: i.notesController.text.trim().isEmpty ? null : i.notesController.text.trim(),
            ),
          )
          .toList();

      final created = await ref
          .read(customOrderRepositoryProvider)
          .submit(addressId: _addressId!, paymentMethod: _paymentMethod, items: items);

      ref.invalidate(myCustomOrderRequestsProvider);

      if (mounted) {
        context.pushReplacement('/custom-order/${created.id}');
      }
    } catch (e) {
      setState(() => _error = apiErrorMessage(e));
    } finally {
      if (mounted) setState(() => _isSubmitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final authState = ref.watch(authControllerProvider);

    return Scaffold(
      appBar: AppBar(title: Text(l10n.customOrderTitle)),
      body: authState.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (error, _) => Center(child: Text(apiErrorMessage(error))),
        data: (user) {
          if (user == null) {
            return Center(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(l10n.customOrderRequireLogin),
                  const SizedBox(height: 12),
                  FilledButton(onPressed: () => context.push('/login'), child: Text(l10n.accountLogin)),
                ],
              ),
            );
          }

          final addressesAsync = ref.watch(addressesProvider);
          final boutiquesAsync = ref.watch(boutiquesProvider);

          return addressesAsync.when(
            loading: () => const Center(child: CircularProgressIndicator()),
            error: (error, _) => Center(child: Text(apiErrorMessage(error))),
            data: (addresses) {
              if (addresses.isEmpty) {
                return Center(
                  child: Padding(
                    padding: const EdgeInsets.all(24),
                    child: Text(l10n.customOrderNeedAddress, textAlign: TextAlign.center),
                  ),
                );
              }

              _addressId ??= addresses.firstWhere((a) => a.isDefault, orElse: () => addresses.first).id;

              return ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  Text(l10n.customOrderIntro, style: Theme.of(context).textTheme.bodyMedium),
                  const SizedBox(height: 16),
                  DropdownButtonFormField<int>(
                    initialValue: _addressId,
                    decoration: InputDecoration(labelText: l10n.customOrderAddress),
                    items: [
                      for (final address in addresses)
                        DropdownMenuItem(
                          value: address.id,
                          child: Text('${address.label} — ${address.city}'),
                        ),
                    ],
                    onChanged: (value) => setState(() => _addressId = value),
                  ),
                  const SizedBox(height: 12),
                  DropdownButtonFormField<String>(
                    initialValue: _paymentMethod,
                    decoration: InputDecoration(labelText: l10n.customOrderPaymentMethod),
                    items: [
                      DropdownMenuItem(value: 'bankily', child: Text(l10n.customOrderPaymentBankily)),
                      DropdownMenuItem(value: 'manual', child: Text(l10n.customOrderPaymentManual)),
                    ],
                    onChanged: (value) => setState(() => _paymentMethod = value ?? _paymentMethod),
                  ),
                  const SizedBox(height: 20),
                  for (var i = 0; i < _items.length; i++)
                    _ItemCard(
                      index: i,
                      item: _items[i],
                      boutiques: boutiquesAsync.valueOrNull ?? [],
                      canRemove: _items.length > 1,
                      onRemove: () => setState(() {
                        _items[i].dispose();
                        _items.removeAt(i);
                      }),
                      onChanged: () => setState(() {}),
                    ),
                  OutlinedButton.icon(
                    onPressed: () => setState(() => _items.add(_ItemFormState())),
                    icon: const Icon(Icons.add),
                    label: Text(l10n.customOrderAddProduct),
                  ),
                  if (_error != null) ...[
                    const SizedBox(height: 12),
                    Text(_error!, style: TextStyle(color: Theme.of(context).colorScheme.error)),
                  ],
                  const SizedBox(height: 20),
                  FilledButton(
                    onPressed: _canSubmit ? _submit : null,
                    child: _isSubmitting
                        ? const SizedBox(
                            height: 18,
                            width: 18,
                            child: CircularProgressIndicator(strokeWidth: 2),
                          )
                        : Text(l10n.customOrderSubmit),
                  ),
                ],
              );
            },
          );
        },
      ),
    );
  }
}

class _ItemCard extends ConsumerStatefulWidget {
  const _ItemCard({
    required this.index,
    required this.item,
    required this.boutiques,
    required this.canRemove,
    required this.onRemove,
    required this.onChanged,
  });

  final int index;
  final _ItemFormState item;
  final List<BoutiqueSummary> boutiques;
  final bool canRemove;
  final VoidCallback onRemove;
  final VoidCallback onChanged;

  @override
  ConsumerState<_ItemCard> createState() => _ItemCardState();
}

class _ItemCardState extends ConsumerState<_ItemCard> {
  Timer? _debounce;
  double? _previewMru;
  bool _isPreviewLoading = false;

  @override
  void dispose() {
    _debounce?.cancel();
    super.dispose();
  }

  /// Interroge /custom-order-price-preview 500 ms après la dernière frappe
  /// (évite une requête par caractère saisi) pour afficher tout de suite
  /// l'équivalent MRU (marge incluse) du prix EUR que le client vient de
  /// lire sur le vrai site externe de la boutique — le client ne doit
  /// jamais voir un prix EUR sans son équivalent MRU (règle transverse).
  void _schedulePreview() {
    _debounce?.cancel();
    final priceEur = double.tryParse(widget.item.priceController.text.trim().replaceAll(',', '.'));

    if (priceEur == null || priceEur <= 0) {
      setState(() => _previewMru = null);
      return;
    }

    _debounce = Timer(const Duration(milliseconds: 500), () async {
      setState(() => _isPreviewLoading = true);
      try {
        final mru = await ref
            .read(customOrderRepositoryProvider)
            .previewPriceMru(priceEur: priceEur, boutiqueId: widget.item.boutiqueId);
        if (mounted) setState(() => _previewMru = mru);
      } catch (_) {
        // Aperçu non bloquant : le prix reste saisissable même hors ligne.
        if (mounted) setState(() => _previewMru = null);
      } finally {
        if (mounted) setState(() => _isPreviewLoading = false);
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Row(
              children: [
                Text('#${widget.index + 1}', style: Theme.of(context).textTheme.titleSmall),
                const Spacer(),
                if (widget.canRemove)
                  IconButton(
                    icon: const Icon(Icons.delete_outline),
                    onPressed: widget.onRemove,
                    tooltip: l10n.customOrderRemoveProduct,
                  ),
              ],
            ),
            if (widget.boutiques.isNotEmpty)
              DropdownButtonFormField<int?>(
                initialValue: widget.item.boutiqueId,
                decoration: InputDecoration(labelText: l10n.customOrderBoutique),
                items: [
                  const DropdownMenuItem<int?>(value: null, child: Text('—')),
                  for (final boutique in widget.boutiques)
                    DropdownMenuItem<int?>(value: boutique.id, child: Text(boutique.name)),
                ],
                onChanged: (value) {
                  widget.item.boutiqueId = value;
                  widget.onChanged();
                  _schedulePreview();
                },
              ),
            const SizedBox(height: 8),
            TextField(
              controller: widget.item.productUrlController,
              keyboardType: TextInputType.url,
              decoration: InputDecoration(labelText: l10n.customOrderProductUrl),
              onChanged: (_) => widget.onChanged(),
            ),
            const SizedBox(height: 8),
            Row(
              children: [
                Expanded(
                  child: TextField(
                    controller: widget.item.quantityController,
                    keyboardType: TextInputType.number,
                    decoration: InputDecoration(labelText: l10n.customOrderQuantity),
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: TextField(
                    controller: widget.item.priceController,
                    keyboardType: const TextInputType.numberWithOptions(decimal: true),
                    decoration: InputDecoration(labelText: l10n.customOrderEstimatedPrice),
                    onChanged: (_) {
                      widget.onChanged();
                      _schedulePreview();
                    },
                  ),
                ),
              ],
            ),
            if (_isPreviewLoading || _previewMru != null) ...[
              const SizedBox(height: 4),
              Padding(
                padding: const EdgeInsetsDirectional.only(start: 4),
                child: _isPreviewLoading
                    ? const SizedBox(
                        height: 12,
                        width: 12,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      )
                    : Text(
                        l10n.customOrderPricePreview(_previewMru!.toStringAsFixed(2)),
                        style: Theme.of(context).textTheme.bodySmall?.copyWith(
                          color: Theme.of(context).colorScheme.primary,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
              ),
            ],
            const SizedBox(height: 8),
            TextField(
              controller: widget.item.notesController,
              decoration: InputDecoration(labelText: l10n.customOrderNotes),
            ),
          ],
        ),
      ),
    );
  }
}
