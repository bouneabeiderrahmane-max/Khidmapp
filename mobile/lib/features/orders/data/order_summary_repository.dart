import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/network/api_client.dart';

/// Résumé minimal d'une commande du client — juste ce qu'il faut pour le
/// sélecteur de commande d'une nouvelle réclamation (module Support). Le
/// module Commandes complet reste un écran de substitution, hors périmètre
/// de cette itération (voir README mobile).
class OrderSummary {
  const OrderSummary({required this.id, required this.statusLabel, required this.totalMru, required this.createdAt});

  factory OrderSummary.fromJson(Map<String, dynamic> json) => OrderSummary(
    id: json['id'] as int,
    statusLabel: json['status_label'] as String,
    totalMru: num.parse(json['total_mru'].toString()).toDouble(),
    createdAt: DateTime.parse(json['created_at'] as String),
  );

  final int id;
  final String statusLabel;
  final double totalMru;
  final DateTime createdAt;
}

class OrderSummaryRepository {
  OrderSummaryRepository(this._dio);

  final Dio _dio;

  Future<List<OrderSummary>> fetchMyOrders() async {
    final response = await _dio.get('/orders');
    return (response.data['data'] as List)
        .map((json) => OrderSummary.fromJson(json as Map<String, dynamic>))
        .toList();
  }
}

final orderSummaryRepositoryProvider = Provider<OrderSummaryRepository>((ref) {
  return OrderSummaryRepository(ref.watch(apiClientProvider).dio);
});
