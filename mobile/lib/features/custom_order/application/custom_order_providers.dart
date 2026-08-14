import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../data/custom_order_models.dart';
import '../data/custom_order_repository.dart';

final myCustomOrderRequestsProvider = FutureProvider<List<CustomOrderRequest>>((ref) {
  return ref.watch(customOrderRepositoryProvider).fetchMyRequests();
});

final customOrderRequestDetailProvider = FutureProvider.family<CustomOrderRequest, int>((ref, id) {
  return ref.watch(customOrderRepositoryProvider).fetchRequest(id);
});
