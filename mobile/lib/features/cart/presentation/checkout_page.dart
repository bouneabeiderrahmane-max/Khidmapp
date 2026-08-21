import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../../core/models/weight_tier.dart';
import '../../../core/network/api_exception.dart';
import '../../../core/widgets/weight_tier_selector.dart';
import '../../../l10n/generated/app_localizations.dart';
import '../../account/application/account_providers.dart';
import '../../account/data/account_models.dart';
import '../../auth/application/auth_controller.dart';
import '../../orders/application/order_providers.dart';
import '../../orders/data/order_summary_repository.dart';
import '../application/cart_providers.dart';
import '../data/cart_models.dart';

class CheckoutPage extends ConsumerStatefulWidget {
  const CheckoutPage({super.key});

  @override
  ConsumerState<CheckoutPage> createState() => _CheckoutPageState();
}

class _CheckoutPageState extends ConsumerState<CheckoutPage> {
  int _currentStep = 0;
  int? _addressId;
  String _paymentMethod = 'bankily';
  WeightTierOption _weightTier = WeightTierOption.petit;
  final _extraWeightController = TextEditingController();
  bool _isSubmitting = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    // Le récapitulatif de coûts (étape Paiement) doit se mettre à jour dès
    // que le client saisit un poids supplémentaire — TextEditingController
    // ne déclenche pas de reconstruction tout seul.
    _extraWeightController.addListener(() => setState(() {}));
  }

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

              final cartAsync = ref.watch(cartProvider);

              return cartAsync.when(
                loading: () => const Center(child: CircularProgressIndicator()),
                error: (error, _) => Center(child: Text(apiErrorMessage(error))),
                data: (cart) => _CheckoutStepper(
                  currentStep: _currentStep,
                  onStepChanged: (step) => setState(() => _currentStep = step),
                  addresses: addresses,
                  addressId: _addressId,
                  onAddressChanged: (value) => setState(() => _addressId = value),
                  weightTier: _weightTier,
                  onWeightTierChanged: (tier) => setState(() => _weightTier = tier),
                  extraWeightController: _extraWeightController,
                  paymentMethod: _paymentMethod,
                  onPaymentMethodChanged: (value) => setState(() => _paymentMethod = value ?? _paymentMethod),
                  cart: cart,
                  isSubmitting: _isSubmitting,
                  error: _error,
                  onConfirm: _confirm,
                ),
              );
            },
          );
        },
      ),
    );
  }
}

/// Trois étapes numérotées (Adresse → Mode de livraison → Paiement), sur le
/// modèle de l'app de référence "achat par proxy" dont des captures ont été
/// fournies — remplace l'ancien formulaire à liste unique.
class _CheckoutStepper extends StatelessWidget {
  const _CheckoutStepper({
    required this.currentStep,
    required this.onStepChanged,
    required this.addresses,
    required this.addressId,
    required this.onAddressChanged,
    required this.weightTier,
    required this.onWeightTierChanged,
    required this.extraWeightController,
    required this.paymentMethod,
    required this.onPaymentMethodChanged,
    required this.cart,
    required this.isSubmitting,
    required this.error,
    required this.onConfirm,
  });

  final int currentStep;
  final ValueChanged<int> onStepChanged;
  final List<Address> addresses;
  final int? addressId;
  final ValueChanged<int?> onAddressChanged;
  final WeightTierOption weightTier;
  final ValueChanged<WeightTierOption> onWeightTierChanged;
  final TextEditingController extraWeightController;
  final String paymentMethod;
  final ValueChanged<String?> onPaymentMethodChanged;
  final CartSummary cart;
  final bool isSubmitting;
  final String? error;
  final VoidCallback onConfirm;

  /// Livraison + coût de gestion recalculés côté client selon le palier de
  /// poids choisi : `GET /cart` renvoie ces deux valeurs avec l'ancienne
  /// estimation par tranche de prix (aucun poids choisi pour l'instant à
  /// ce stade) — on en déduit le pourcentage de coût de gestion réel
  /// (managementFeeMru / (subtotal+delivery)) pour recalculer un total
  /// exact avec le palier réellement sélectionné, sans dupliquer ce
  /// pourcentage en dur côté app (voir config('pricing.management_fee_percent')
  /// côté backend).
  double get _extraWeightKg => double.tryParse(extraWeightController.text.trim().replaceAll(',', '.')) ?? 0;

  double get _chosenDeliveryFeeMru =>
      weightTier.feeMru + (weightTier.acceptsExtraWeight ? _extraWeightKg * WeightTierOption.extraKgFeeMru : 0);

  double get _managementFeePercent {
    final base = cart.subtotalMru + cart.deliveryFeeMru;
    return base > 0 ? cart.managementFeeMru / base : 0;
  }

  double get _chosenManagementFeeMru => double.parse((_managementFeePercent * (cart.subtotalMru + _chosenDeliveryFeeMru)).toStringAsFixed(2));

  double get _chosenTotalMru => cart.subtotalMru + _chosenDeliveryFeeMru + _chosenManagementFeeMru;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);

    return Stepper(
      currentStep: currentStep,
      onStepTapped: onStepChanged,
      onStepContinue: () {
        if (currentStep < 2) {
          onStepChanged(currentStep + 1);
        } else {
          onConfirm();
        }
      },
      onStepCancel: currentStep > 0 ? () => onStepChanged(currentStep - 1) : null,
      controlsBuilder: (context, details) {
        return Padding(
          padding: const EdgeInsets.only(top: 12),
          child: Row(
            children: [
              FilledButton(
                onPressed: isSubmitting ? null : details.onStepContinue,
                child: currentStep == 2
                    ? (isSubmitting
                          ? const SizedBox(
                              height: 18,
                              width: 18,
                              child: CircularProgressIndicator(strokeWidth: 2),
                            )
                          : Text(l10n.checkoutConfirm))
                    : Text(l10n.checkoutContinue),
              ),
              if (details.onStepCancel != null) ...[
                const SizedBox(width: 8),
                TextButton(onPressed: details.onStepCancel, child: Text(l10n.checkoutBack)),
              ],
            ],
          ),
        );
      },
      steps: [
        Step(
          title: Text(l10n.checkoutStepAddress),
          isActive: currentStep >= 0,
          state: currentStep > 0 ? StepState.complete : StepState.indexed,
          content: DropdownButtonFormField<int>(
            initialValue: addressId,
            decoration: InputDecoration(labelText: l10n.checkoutAddress),
            items: [
              for (final address in addresses)
                DropdownMenuItem(value: address.id, child: Text('${address.label} — ${address.city}')),
            ],
            onChanged: onAddressChanged,
          ),
        ),
        Step(
          title: Text(l10n.checkoutStepDelivery),
          isActive: currentStep >= 1,
          state: currentStep > 1 ? StepState.complete : StepState.indexed,
          content: WeightTierSelector(
            value: weightTier,
            onChanged: onWeightTierChanged,
            extraWeightController: extraWeightController,
          ),
        ),
        Step(
          title: Text(l10n.checkoutStepPayment),
          isActive: currentStep >= 2,
          state: StepState.indexed,
          content: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              DropdownButtonFormField<String>(
                initialValue: paymentMethod,
                decoration: InputDecoration(labelText: l10n.checkoutPaymentMethod),
                items: [
                  DropdownMenuItem(value: 'bankily', child: Text(l10n.checkoutPaymentBankily)),
                  DropdownMenuItem(value: 'manual', child: Text(l10n.checkoutPaymentManual)),
                ],
                onChanged: onPaymentMethodChanged,
              ),
              const SizedBox(height: 12),
              Text(
                paymentMethod == 'bankily' ? l10n.checkoutBankilyInstructions : l10n.checkoutManualInstructions,
                style: Theme.of(
                  context,
                ).textTheme.bodySmall?.copyWith(color: Theme.of(context).colorScheme.outline),
              ),
              const SizedBox(height: 16),
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(12),
                  child: Column(
                    children: [
                      _SummaryRow(label: l10n.cartSubtotal, value: cart.subtotalMru),
                      _SummaryRow(label: l10n.cartDeliveryFee, value: _chosenDeliveryFeeMru),
                      _SummaryRow(label: l10n.cartManagementFee, value: _chosenManagementFeeMru),
                      const Divider(),
                      _SummaryRow(label: l10n.cartTotal, value: _chosenTotalMru, emphasize: true),
                    ],
                  ),
                ),
              ),
              if (error != null) ...[
                const SizedBox(height: 12),
                Text(error!, style: TextStyle(color: Theme.of(context).colorScheme.error)),
              ],
            ],
          ),
        ),
      ],
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
