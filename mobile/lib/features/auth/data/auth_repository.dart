import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/network/api_client.dart';

class AuthUser {
  const AuthUser({
    required this.id,
    required this.name,
    this.phone,
    this.email,
    required this.roles,
  });

  factory AuthUser.fromJson(Map<String, dynamic> json) => AuthUser(
    id: json['id'] as int,
    name: json['name'] as String,
    phone: json['phone'] as String?,
    email: json['email'] as String?,
    roles: (json['roles'] as List).map((r) => r.toString()).toList(),
  );

  final int id;
  final String name;
  final String? phone;
  final String? email;
  final List<String> roles;
}

class AuthSession {
  const AuthSession({required this.token, required this.user});

  factory AuthSession.fromJson(Map<String, dynamic> json) => AuthSession(
    token: json['access_token'] as String,
    user: AuthUser.fromJson(json['user'] as Map<String, dynamic>),
  );

  final String token;
  final AuthUser user;
}

class AuthRepository {
  AuthRepository(this._dio);

  final Dio _dio;

  Future<void> requestOtp(String phone) async {
    await _dio.post('/auth/otp/request', data: {'phone': phone});
  }

  Future<AuthSession> verifyOtp({
    required String phone,
    required String code,
    String? name,
  }) async {
    final response = await _dio.post(
      '/auth/otp/verify',
      data: {'phone': phone, 'code': code, if (name != null) 'name': name},
    );
    return AuthSession.fromJson(response.data as Map<String, dynamic>);
  }

  Future<AuthUser> me() async {
    final response = await _dio.get('/me');
    return AuthUser.fromJson(response.data['data'] as Map<String, dynamic>);
  }
}

final authRepositoryProvider = Provider<AuthRepository>((ref) {
  return AuthRepository(ref.watch(apiClientProvider).dio);
});
