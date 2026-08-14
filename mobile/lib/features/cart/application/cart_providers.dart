import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../data/cart_models.dart';
import '../data/cart_repository.dart';

final cartProvider = FutureProvider<CartSummary>((ref) {
  return ref.watch(cartRepositoryProvider).fetchCart();
});
