import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../data/order_summary_repository.dart';

final myOrdersProvider = FutureProvider<List<OrderSummary>>((ref) {
  return ref.watch(orderSummaryRepositoryProvider).fetchMyOrders();
});

final orderDetailProvider = FutureProvider.family<OrderDetail, int>((ref, id) {
  return ref.watch(orderSummaryRepositoryProvider).fetchOrder(id);
});
