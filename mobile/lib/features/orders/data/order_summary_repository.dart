import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/models/localized_text.dart';
import '../../../core/network/api_client.dart';

/// Résumé minimal d'une commande — utilisé par le sélecteur de commande
/// d'une nouvelle réclamation (module Support) et par la liste de
/// l'onglet Commandes.
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

class OrderItem {
  const OrderItem({
    required this.id,
    required this.productName,
    this.size,
    this.color,
    required this.quantity,
    required this.unitPriceMru,
    required this.lineSubtotalMru,
  });

  factory OrderItem.fromJson(Map<String, dynamic> json) => OrderItem(
    id: json['id'] as int,
    productName: LocalizedText.fromJson(json['product_name'] as Map<String, dynamic>),
    size: json['size'] as String?,
    color: json['color'] as String?,
    quantity: json['quantity'] as int,
    unitPriceMru: num.parse(json['unit_price_mru'].toString()).toDouble(),
    lineSubtotalMru: num.parse(json['line_subtotal_mru'].toString()).toDouble(),
  );

  final int id;
  final LocalizedText productName;
  final String? size;
  final String? color;
  final int quantity;
  final double unitPriceMru;
  final double lineSubtotalMru;
}

class OrderStatusHistoryEntry {
  const OrderStatusHistoryEntry({
    required this.toStatusLabel,
    required this.actorType,
    this.note,
    required this.createdAt,
  });

  factory OrderStatusHistoryEntry.fromJson(Map<String, dynamic> json) => OrderStatusHistoryEntry(
    toStatusLabel: json['to_status_label'] as String,
    actorType: json['actor_type'] as String,
    note: json['note'] as String?,
    createdAt: DateTime.parse(json['created_at'] as String),
  );

  final String toStatusLabel;
  final String actorType;
  final String? note;
  final DateTime createdAt;
}

class OrderPayment {
  const OrderPayment({
    required this.methodLabel,
    required this.statusLabel,
    required this.amountMru,
    this.proofDownloadUrl,
  });

  factory OrderPayment.fromJson(Map<String, dynamic> json) => OrderPayment(
    methodLabel: json['method_label'] as String,
    statusLabel: json['status_label'] as String,
    amountMru: num.parse(json['amount_mru'].toString()).toDouble(),
    proofDownloadUrl: json['proof_download_url'] as String?,
  );

  final String methodLabel;
  final String statusLabel;
  final double amountMru;
  final String? proofDownloadUrl;
}

class OrderShipping {
  const OrderShipping({required this.label, required this.city, this.area, required this.phone});

  factory OrderShipping.fromJson(Map<String, dynamic> json) => OrderShipping(
    label: json['label'] as String,
    city: json['city'] as String,
    area: json['area'] as String?,
    phone: json['phone'] as String,
  );

  final String label;
  final String city;
  final String? area;
  final String phone;
}

class OrderDetail {
  const OrderDetail({
    required this.id,
    required this.status,
    required this.statusLabel,
    required this.shipping,
    this.paymentMethod,
    required this.subtotalMru,
    required this.deliveryFeeMru,
    required this.managementFeeMru,
    this.deliveryZone,
    this.weightTierLabel,
    this.extraWeightKg,
    required this.totalMru,
    this.cancellationReason,
    this.refundReason,
    required this.items,
    required this.statusHistory,
    required this.payments,
    required this.createdAt,
  });

  factory OrderDetail.fromJson(Map<String, dynamic> json) => OrderDetail(
    id: json['id'] as int,
    status: json['status'] as String,
    statusLabel: json['status_label'] as String,
    shipping: OrderShipping.fromJson(json['shipping'] as Map<String, dynamic>),
    paymentMethod: json['payment_method'] as String?,
    subtotalMru: num.parse(json['subtotal_mru'].toString()).toDouble(),
    deliveryFeeMru: num.parse(json['delivery_fee_mru'].toString()).toDouble(),
    managementFeeMru: num.parse(json['management_fee_mru'].toString()).toDouble(),
    deliveryZone: json['delivery_zone'] as String?,
    weightTierLabel: json['weight_tier_label'] as String?,
    extraWeightKg: json['extra_weight_kg'] == null ? null : num.parse(json['extra_weight_kg'].toString()).toDouble(),
    totalMru: num.parse(json['total_mru'].toString()).toDouble(),
    cancellationReason: json['cancellation_reason'] as String?,
    refundReason: json['refund_reason'] as String?,
    items: (json['items'] as List).map((i) => OrderItem.fromJson(i as Map<String, dynamic>)).toList(),
    statusHistory: (json['status_history'] as List)
        .map((h) => OrderStatusHistoryEntry.fromJson(h as Map<String, dynamic>))
        .toList(),
    payments: (json['payments'] as List).map((p) => OrderPayment.fromJson(p as Map<String, dynamic>)).toList(),
    createdAt: DateTime.parse(json['created_at'] as String),
  );

  final int id;
  final String status;
  final String statusLabel;
  final OrderShipping shipping;
  final String? paymentMethod;
  final double subtotalMru;
  final double deliveryFeeMru;
  final double managementFeeMru;
  final String? deliveryZone;
  final String? weightTierLabel;
  final double? extraWeightKg;
  final double totalMru;
  final String? cancellationReason;
  final String? refundReason;
  final List<OrderItem> items;
  final List<OrderStatusHistoryEntry> statusHistory;
  final List<OrderPayment> payments;
  final DateTime createdAt;

  static const _cancellableStatuses = ['annule', 'rembourse', 'livre'];

  bool get canCancel => !_cancellableStatuses.contains(status);
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

  Future<OrderDetail> fetchOrder(int id) async {
    final response = await _dio.get('/orders/$id');
    return OrderDetail.fromJson(response.data['data'] as Map<String, dynamic>);
  }

  Future<OrderDetail> createOrder({
    required int addressId,
    required String paymentMethod,
    required String weightTier,
    double? extraWeightKg,
  }) async {
    final response = await _dio.post(
      '/orders',
      data: {
        'address_id': addressId,
        'payment_method': paymentMethod,
        'weight_tier': weightTier,
        if (extraWeightKg != null && extraWeightKg > 0) 'extra_weight_kg': extraWeightKg,
      },
    );
    return OrderDetail.fromJson(response.data['data'] as Map<String, dynamic>);
  }

  Future<OrderDetail> cancelOrder(int id, {String? reason}) async {
    final response = await _dio.post(
      '/orders/$id/cancel',
      data: {if (reason != null && reason.isNotEmpty) 'reason': reason},
    );
    return OrderDetail.fromJson(response.data['data'] as Map<String, dynamic>);
  }

  /// Crée (ou réutilise, si déjà en cours) la transaction Bankily côté
  /// serveur (StubBankilyGateway, placeholder — voir son docblock) ; la
  /// confirmation réelle du paiement arrive plus tard par webhook, pas de
  /// retour synchrone à afficher au-delà du message d'instructions déjà
  /// montré à l'écran (voir CheckoutPage).
  Future<void> initiateBankilyPayment(int orderId) {
    return _dio.post('/orders/$orderId/payments/bankily/initiate');
  }
}

final orderSummaryRepositoryProvider = Provider<OrderSummaryRepository>((ref) {
  return OrderSummaryRepository(ref.watch(apiClientProvider).dio);
});
