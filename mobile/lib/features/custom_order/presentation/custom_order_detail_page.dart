import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/network/api_exception.dart';
import '../../../l10n/generated/app_localizations.dart';
import '../application/custom_order_providers.dart';
import '../data/custom_order_models.dart';

class CustomOrderDetailPage extends ConsumerWidget {
  const CustomOrderDetailPage({super.key, required this.requestId});

  final int requestId;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    final requestAsync = ref.watch(customOrderRequestDetailProvider(requestId));

    return Scaffold(
      appBar: AppBar(title: Text('${l10n.customOrderTitle} #$requestId')),
      body: requestAsync.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (error, _) => Center(child: Text(apiErrorMessage(error))),
        data: (request) => ListView(
          padding: const EdgeInsets.all(16),
          children: [
            Text(request.statusLabel, style: Theme.of(context).textTheme.titleLarge),
            const SizedBox(height: 16),
            if (!request.isRejected) ...[
              Text(l10n.customOrderStages, style: Theme.of(context).textTheme.titleSmall),
              const SizedBox(height: 8),
              _StageTracker(stages: request.stages, currentStage: request.stage),
              const SizedBox(height: 20),
            ],
            if (request.adminNote != null) ...[
              Card(
                color: request.isRejected
                    ? Theme.of(context).colorScheme.errorContainer
                    : Theme.of(context).colorScheme.surfaceContainerHighest,
                child: Padding(
                  padding: const EdgeInsets.all(12),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        l10n.customOrderAdminNote,
                        style: Theme.of(context).textTheme.labelMedium,
                      ),
                      const SizedBox(height: 4),
                      Text(request.adminNote!),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 16),
            ],
            if (request.linkedOrder != null) ...[
              Card(
                child: ListTile(
                  leading: const Icon(Icons.receipt_long_outlined),
                  title: Text(l10n.customOrderLinkedOrder(request.linkedOrder!.id)),
                  subtitle: Text(
                    '${request.linkedOrder!.statusLabel} · ${request.linkedOrder!.totalMru.toStringAsFixed(2)} MRU',
                  ),
                ),
              ),
              const SizedBox(height: 16),
            ],
            for (final item in request.items) _ItemTile(item: item),
          ],
        ),
      ),
    );
  }
}

class _StageTracker extends StatelessWidget {
  const _StageTracker({required this.stages, required this.currentStage});

  final List<CustomOrderStageInfo> stages;
  final String? currentStage;

  @override
  Widget build(BuildContext context) {
    final currentIndex = stages.indexWhere((s) => s.key == currentStage);

    return Column(
      children: [
        for (var i = 0; i < stages.length; i++)
          _StageRow(
            label: stages[i].label,
            isDone: currentIndex >= 0 && i < currentIndex,
            isCurrent: i == currentIndex,
            isLast: i == stages.length - 1,
          ),
      ],
    );
  }
}

class _StageRow extends StatelessWidget {
  const _StageRow({
    required this.label,
    required this.isDone,
    required this.isCurrent,
    required this.isLast,
  });

  final String label;
  final bool isDone;
  final bool isCurrent;
  final bool isLast;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    final color = (isDone || isCurrent) ? scheme.primary : scheme.outlineVariant;

    return IntrinsicHeight(
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Column(
            children: [
              Icon(
                isDone ? Icons.check_circle : (isCurrent ? Icons.radio_button_checked : Icons.circle_outlined),
                color: color,
                size: 20,
              ),
              if (!isLast) Expanded(child: Container(width: 2, color: color)),
            ],
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Padding(
              padding: const EdgeInsets.only(bottom: 16),
              child: Text(
                label,
                style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                  fontWeight: isCurrent ? FontWeight.bold : FontWeight.normal,
                  color: (isDone || isCurrent) ? null : scheme.outline,
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _ItemTile extends StatelessWidget {
  const _ItemTile({required this.item});

  final CustomOrderItem item;

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      child: ListTile(
        title: Text(
          item.productUrl,
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
        ),
        subtitle: Text(
          [
            if (item.boutiqueName != null) item.boutiqueName,
            '×${item.quantity}',
            '${item.estimatedPriceEur.toStringAsFixed(2)} €',
            if (item.notes != null) item.notes,
          ].join(' · '),
        ),
      ),
    );
  }
}
