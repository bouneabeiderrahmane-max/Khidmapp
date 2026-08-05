import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../data/account_models.dart';
import '../data/account_repository.dart';

final addressesProvider = FutureProvider<List<Address>>((ref) {
  return ref.watch(accountRepositoryProvider).fetchAddresses();
});

final notificationPreferencesProvider = FutureProvider<NotificationPreferences>((ref) {
  return ref.watch(accountRepositoryProvider).fetchNotificationPreferences();
});
