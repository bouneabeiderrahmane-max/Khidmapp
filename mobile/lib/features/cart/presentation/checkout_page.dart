import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../../core/models/weight_tier.dart';
import '../../../core/network/api_exception.dart';
import '../../../core/widgets/weight_tier_selector.dart';
import '../../../l10n/generated/app_localizations.dart';
import '../../account/application/account_providers.dart';
import '../../auth/application/auth_controller.dart';
import '../../orders/application/order_providers.dart';
import '../../orders/data/order_summary_repository.dart';
import '../application/cart_providers.dart';

class CheckoutPage extends ConsumerStatefulWidget {
  const CheckoutPage({super.key});

  @override
  ConsumerState<CheckoutPage> createState() => _CheckoutPageState();
}

class _CheckoutPageState extends ConsumerState<CheckoutPage> {
  int? _addressId;
  String _paymentMethod = 'bankily';
  WeightTierOption _weightTier = WeightTierOption.petit;
  final _extraWeightController = TextEditingController();
  bool _isSubmitting = false;
  String? _error;

  @override
  void dispose() {
    _extraWeightController.dispose();
    super.dispose();
  }

  Future<void> _confirm() async {
    if (_addressId == null) return;

    setState(() {
      _isSubmitting = true;
      _error = null;
    });
    try {
      final extraWeightKg = double.tryParse(_extraWeightController.text.trim().replaceAll(',', '.'));
      final order = await ref
          .read(orderSummaryRepositoryProvider)
          .createOrder(
            addressId: _addressId!,
            paymentMethod: _paymentMethod,
            weightTier: _weightTier.key,
            extraWeightKg: extraWeightKg,
          );

      if (_paymentMethod == 'bankily') {
        try {
          await ref.read(orderSummaryRepositoryProvider).initiateBankilyPayment(order.id);
        } catch (_) {
          // Non bloquant : la commande existe déjà, le client peut retenter
          // depuis l'écran de détail si l'initiation échoue.
        }
      }

      ref.invalidate(cartProvider);
      ref.invalidate(myOrdersProvider);

      if (mounted) {
        context.pushReplacement('/orders/${order.id}');
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
      appBar: AppBar(title: Text(l10n.checkoutTitle)),
      body: authState.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (error, _) => Center(child: Text(apiErrorMessage(error))),
        data: (user) {
          if (user == null) {
            return Center(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(l10n.checkoutRequireLogin),
                  const SizedBox(height: 12),
                  FilledButton(onPressed: () => context.push('/login'), child: Text(l10n.accountLogin)),
                ],
              ),
            );
          }

          final addressesAsync = ref.watch(addressesProvider);

          return addressesAsync.when(
            loading: () => const Center(child: CircularProgressIndicator()),
            error: (error, _) => Center(child: Text(apiErrorMessage(error))),
            data: (addresses) {
              if (addresses.isEmpty) {
                return Center(
                  child: Padding(
                    padding: const EdgeInsets.all(24),
                    child: Text(l10n.checkoutNeedAddress, textAlign: TextAlign.center),
                  ),
                );
              }

              _addressId ??= addresses.firstWhere((a) => a.isDefault, orElse: () => addresses.first).id;

              return ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  DropdownButtonFormField<int>(
                    initialValue: _addressId,
                    decoration: InputDecoration(labelText: l10n.checkoutAddress),
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
                    decoration: InputDecoration(labelText: l10n.checkoutPaymentMethod),
                    items: [
                      DropdownMenuItem(value: 'bankily', child: Text(l10n.checkoutPaymentBankily)),
                      DropdownMenuItem(value: 'manual', child: Text(l10n.checkoutPaymentManual)),
                    ],
                    onChanged: (value) => setState(() => _paymentMethod = value ?? _paymentMethod),
                  ),
                  const SizedBox(height: 20),
                  WeightTierSelector(
                    value: _weightTier,
                    onChanged: (tier) => setState(() => _weightTier = tier),
                    extraWeightController: _extraWeightController,
                  ),
                  const SizedBox(height: 12),
                  Text(
                    _paymentMethod == 'bankily' ? l10n.checkoutBankilyInstructions : l10n.checkoutManualInstructions,
                    style: Theme.of(context).textTheme.bodySmall?.copyWith(
                      color: Theme.of(context).colorScheme.outline,
                    ),
                  ),
                  if (_error != null) ...[
                    const SizedBox(height: 12),
                    Text(_error!, style: TextStyle(color: Theme.of(context).colorScheme.error)),
                  ],
                  const SizedBox(height: 20),
                  FilledButton(
                    onPressed: _isSubmitting ? null : _confirm,
                    child: _isSubmitting
                        ? const SizedBox(
                            height: 18,
                            width: 18,
                            child: CircularProgressIndicator(strokeWidth: 2),
                          )
                        : Text(l10n.checkoutConfirm),
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
