import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../../core/network/api_exception.dart';
import '../../../l10n/generated/app_localizations.dart';
import '../../auth/application/auth_controller.dart';
import '../application/order_providers.dart';

class OrdersListPage extends ConsumerWidget {
  const OrdersListPage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    final authState = ref.watch(authControllerProvider);

    return Scaffold(
      appBar: AppBar(title: Text(l10n.navOrders)),
      body: authState.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (error, _) => Center(child: Text(apiErrorMessage(error))),
        data: (user) {
          if (user == null) {
            return Center(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(l10n.accountNotConnected),
                  const SizedBox(height: 12),
                  FilledButton(onPressed: () => context.push('/login'), child: Text(l10n.accountLogin)),
                ],
              ),
            );
          }

          final ordersAsync = ref.watch(myOrdersProvider);

          return ordersAsync.when(
            loading: () => const Center(child: CircularProgressIndicator()),
            error: (error, _) => Center(child: Text(apiErrorMessage(error))),
            data: (orders) {
              if (orders.isEmpty) {
                return Center(child: Text(l10n.ordersEmpty));
              }
              return RefreshIndicator(
                onRefresh: () => ref.refresh(myOrdersProvider.future),
                child: ListView.separated(
                  padding: const EdgeInsets.all(12),
                  itemCount: orders.length,
                  separatorBuilder: (_, __) => const SizedBox(height: 8),
                  itemBuilder: (context, index) {
                    final order = orders[index];
                    return Card(
                      child: ListTile(
                        title: Text('#${order.id} — ${order.statusLabel}'),
                        subtitle: Text('${order.totalMru.toStringAsFixed(2)} MRU'),
                        trailing: const Icon(Icons.chevron_right),
                        onTap: () => context.push('/orders/${order.id}'),
                      ),
                    );
                  },
                ),
              );
            },
          );
        },
      ),
    );
  }
}
