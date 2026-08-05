import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/network/api_client.dart';
import 'support_models.dart';

class SupportRepository {
  SupportRepository(this._dio);

  final Dio _dio;

  Future<List<Faq>> fetchFaqs() async {
    final response = await _dio.get('/faqs');
    return (response.data['data'] as List).map((json) => Faq.fromJson(json as Map<String, dynamic>)).toList();
  }

  Future<List<Complaint>> fetchComplaints() async {
    final response = await _dio.get('/complaints');
    return (response.data['data'] as List)
        .map((json) => Complaint.fromJson(json as Map<String, dynamic>))
        .toList();
  }

  Future<Complaint> fetchComplaint(int id) async {
    final response = await _dio.get('/complaints/$id');
    return Complaint.fromJson(response.data['data'] as Map<String, dynamic>);
  }

  Future<Complaint> createComplaint({required int orderId, required String category, required String message}) async {
    final response = await _dio.post(
      '/complaints',
      data: {'order_id': orderId, 'category': category, 'message': message},
    );
    return Complaint.fromJson(response.data['data'] as Map<String, dynamic>);
  }

  Future<void> addMessage(int complaintId, String message) {
    return _dio.post('/complaints/$complaintId/messages', data: {'message': message});
  }
}

final supportRepositoryProvider = Provider<SupportRepository>((ref) {
  return SupportRepository(ref.watch(apiClientProvider).dio);
});
