import 'package:flutter/material.dart';
import '../../l10n/generated/app_localizations.dart';
import '../models/weight_tier.dart';

/// Sélecteur du poids de colis au paiement (panier ou commande
/// personnalisée), sur le modèle de l'app de référence "achat par proxy" :
/// trois cartes à choix unique, le palier le plus grand révélant un champ
/// de poids supplémentaire optionnel (facturé au-delà de 15 kg).
class WeightTierSelector extends StatelessWidget {
  const WeightTierSelector({
    super.key,
    required this.value,
    required this.onChanged,
    required this.extraWeightController,
  });

  final WeightTierOption? value;
  final ValueChanged<WeightTierOption> onChanged;
  final TextEditingController extraWeightController;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(l10n.weightTierTitle, style: Theme.of(context).textTheme.titleSmall),
        const SizedBox(height: 4),
        Text(l10n.weightTierSubtitle, style: Theme.of(context).textTheme.bodySmall),
        const SizedBox(height: 8),
        for (final tier in WeightTierOption.values)
          _WeightTierCard(
            tier: tier,
            selected: value == tier,
            label: _labelFor(l10n, tier),
            range: _rangeFor(l10n, tier),
            onTap: () => onChanged(tier),
          ),
        if (value?.acceptsExtraWeight ?? false) ...[
          const SizedBox(height: 8),
          TextField(
            controller: extraWeightController,
            keyboardType: const TextInputType.numberWithOptions(decimal: true),
            decoration: InputDecoration(
              labelText: l10n.weightTierExtraKgLabel,
              helperText: l10n.weightTierExtraKgHint,
            ),
          ),
        ],
      ],
    );
  }

  static String _labelFor(AppLocalizations l10n, WeightTierOption tier) => switch (tier) {
    WeightTierOption.petit => l10n.weightTierPetitLabel,
    WeightTierOption.moyen => l10n.weightTierMoyenLabel,
    WeightTierOption.tresGrand => l10n.weightTierTresGrandLabel,
  };

  static String _rangeFor(AppLocalizations l10n, WeightTierOption tier) => switch (tier) {
    WeightTierOption.petit => l10n.weightTierPetitRange,
    WeightTierOption.moyen => l10n.weightTierMoyenRange,
    WeightTierOption.tresGrand => l10n.weightTierTresGrandRange,
  };
}

class _WeightTierCard extends StatelessWidget {
  const _WeightTierCard({
    required this.tier,
    required this.selected,
    required this.label,
    required this.range,
    required this.onTap,
  });

  final WeightTierOption tier;
  final bool selected;
  final String label;
  final String range;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final colorScheme = Theme.of(context).colorScheme;

    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(8),
        side: BorderSide(color: selected ? colorScheme.primary : colorScheme.outlineVariant),
      ),
      child: InkWell(
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.all(12),
          child: Row(
            children: [
              Icon(
                selected ? Icons.radio_button_checked : Icons.radio_button_unchecked,
                color: selected ? colorScheme.primary : colorScheme.outline,
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(label, style: Theme.of(context).textTheme.titleSmall),
                    Text(
                      range,
                      style: Theme.of(
                        context,
                      ).textTheme.bodySmall?.copyWith(color: colorScheme.outline),
                    ),
                  ],
                ),
              ),
              Text(
                '${tier.feeMru.toStringAsFixed(0)} MRU',
                style: Theme.of(context).textTheme.titleSmall,
              ),
            ],
          ),
        ),
      ),
    );
  }
}
