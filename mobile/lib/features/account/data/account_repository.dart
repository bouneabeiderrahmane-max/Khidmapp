import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/network/api_client.dart';
import 'account_models.dart';

class AccountRepository {
  AccountRepository(this._dio);

  final Dio _dio;

  Future<List<Address>> fetchAddresses() async {
    final response = await _dio.get('/addresses');
    return (response.data['data'] as List).map((json) => Address.fromJson(json as Map<String, dynamic>)).toList();
  }

  Future<void> createAddress({
    required String label,
    required String city,
    String? area,
    String? phone,
    bool isDefault = false,
  }) {
    return _dio.post(
      '/addresses',
      data: {
        'label': label,
        'city': city,
        if (area != null && area.isNotEmpty) 'area': area,
        if (phone != null && phone.isNotEmpty) 'phone': phone,
        'is_default': isDefault,
      },
    );
  }

  Future<void> deleteAddress(int id) {
    return _dio.delete('/addresses/$id');
  }

  Future<NotificationPreferences> fetchNotificationPreferences() async {
    final response = await _dio.get('/notification-preferences');
    return NotificationPreferences.fromJson(response.data['data'] as Map<String, dynamic>);
  }

  Future<NotificationPreferences> updateNotificationPreferences({bool? push, bool? sms, bool? email}) async {
    final response = await _dio.put(
      '/notification-preferences',
      data: {if (push != null) 'push': push, if (sms != null) 'sms': sms, if (email != null) 'email': email},
    );
    return NotificationPreferences.fromJson(response.data['data'] as Map<String, dynamic>);
  }
}

final accountRepositoryProvider = Provider<AccountRepository>((ref) {
  return AccountRepository(ref.watch(apiClientProvider).dio);
});
