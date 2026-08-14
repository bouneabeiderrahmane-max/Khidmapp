import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../../core/network/api_exception.dart';
import '../../../l10n/generated/app_localizations.dart';
import '../application/custom_order_providers.dart';

class CustomOrderListPage extends ConsumerWidget {
  const CustomOrderListPage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    final requestsAsync = ref.watch(myCustomOrderRequestsProvider);

    return Scaffold(
      appBar: AppBar(title: Text(l10n.homeMyCustomOrders)),
      body: requestsAsync.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (error, _) => Center(child: Text(apiErrorMessage(error))),
        data: (requests) {
          if (requests.isEmpty) {
            return Center(child: Text(l10n.customOrderListEmpty));
          }
          return RefreshIndicator(
            onRefresh: () => ref.refresh(myCustomOrderRequestsProvider.future),
            child: ListView.separated(
              padding: const EdgeInsets.all(12),
              itemCount: requests.length,
              separatorBuilder: (_, __) => const SizedBox(height: 8),
              itemBuilder: (context, index) {
                final request = requests[index];
                return Card(
                  child: ListTile(
                    title: Text('#${request.id} — ${request.statusLabel}'),
                    subtitle: Text(request.stageLabel ?? request.statusLabel),
                    trailing: const Icon(Icons.chevron_right),
                    onTap: () => context.push('/custom-order/${request.id}'),
                  ),
                );
              },
            ),
          );
        },
      ),
    );
  }
}
