import '../../../core/models/localized_text.dart';

class CartItem {
  const CartItem({
    required this.id,
    required this.variantId,
    required this.productId,
    required this.productName,
    this.image,
    required this.boutiqueName,
    this.size,
    this.color,
    required this.quantity,
    required this.unitPriceMru,
    required this.lineSubtotalMru,
  });

  factory CartItem.fromJson(Map<String, dynamic> json) {
    final product = json['product'] as Map<String, dynamic>;

    return CartItem(
      id: json['id'] as int,
      variantId: json['variant_id'] as int,
      productId: product['id'] as int,
      productName: LocalizedText.fromJson(product['name'] as Map<String, dynamic>),
      image: product['image'] as String?,
      boutiqueName: product['boutique'] as String,
      size: json['size'] as String?,
      color: json['color'] as String?,
      quantity: json['quantity'] as int,
      unitPriceMru: num.parse(json['unit_price_mru'].toString()).toDouble(),
      lineSubtotalMru: num.parse(json['line_subtotal_mru'].toString()).toDouble(),
    );
  }

  final int id;
  final int variantId;
  final int productId;
  final LocalizedText productName;
  final String? image;
  final String boutiqueName;
  final String? size;
  final String? color;
  final int quantity;
  final double unitPriceMru;
  final double lineSubtotalMru;
}

class CartSummary {
  const CartSummary({
    required this.items,
    required this.subtotalMru,
    this.deliveryZone,
    required this.deliveryFeeMru,
    required this.managementFeeMru,
    required this.totalMru,
  });

  factory CartSummary.fromJson(Map<String, dynamic> json) => CartSummary(
    items: (json['items'] as List).map((i) => CartItem.fromJson(i as Map<String, dynamic>)).toList(),
    subtotalMru: num.parse(json['subtotal_mru'].toString()).toDouble(),
    deliveryZone: json['delivery_zone'] as String?,
    deliveryFeeMru: num.parse(json['delivery_fee_mru'].toString()).toDouble(),
    managementFeeMru: num.parse(json['management_fee_mru'].toString()).toDouble(),
    totalMru: num.parse(json['total_mru'].toString()).toDouble(),
  );

  final List<CartItem> items;
  final double subtotalMru;
  final String? deliveryZone;
  final double deliveryFeeMru;
  final double managementFeeMru;
  final double totalMru;

  bool get isEmpty => items.isEmpty;
}
