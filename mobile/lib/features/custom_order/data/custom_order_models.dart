class CustomOrderStageInfo {
  const CustomOrderStageInfo({required this.key, required this.label});

  factory CustomOrderStageInfo.fromJson(Map<String, dynamic> json) =>
      CustomOrderStageInfo(key: json['key'] as String, label: json['label'] as String);

  final String key;
  final String label;
}

class CustomOrderItem {
  const CustomOrderItem({
    required this.id,
    this.boutiqueName,
    required this.productUrl,
    required this.quantity,
    required this.estimatedPriceEur,
    this.notes,
  });

  factory CustomOrderItem.fromJson(Map<String, dynamic> json) => CustomOrderItem(
    id: json['id'] as int,
    boutiqueName: (json['boutique'] as Map<String, dynamic>?)?['name'] as String?,
    productUrl: json['product_url'] as String,
    quantity: json['quantity'] as int,
    estimatedPriceEur: (json['estimated_price_eur'] as num).toDouble(),
    notes: json['notes'] as String?,
  );

  final int id;
  final String? boutiqueName;
  final String productUrl;
  final int quantity;
  final double estimatedPriceEur;
  final String? notes;
}

class LinkedOrder {
  const LinkedOrder({required this.id, required this.statusLabel, required this.totalMru});

  factory LinkedOrder.fromJson(Map<String, dynamic> json) => LinkedOrder(
    id: json['id'] as int,
    statusLabel: json['status_label'] as String,
    totalMru: (json['total_mru'] as num).toDouble(),
  );

  final int id;
  final String statusLabel;
  final double totalMru;
}

class CustomOrderRequest {
  const CustomOrderRequest({
    required this.id,
    required this.status,
    required this.statusLabel,
    this.stage,
    this.stageLabel,
    required this.stages,
    this.adminNote,
    this.linkedOrder,
    required this.items,
    required this.createdAt,
  });

  factory CustomOrderRequest.fromJson(Map<String, dynamic> json) => CustomOrderRequest(
    id: json['id'] as int,
    status: json['status'] as String,
    statusLabel: json['status_label'] as String,
    stage: json['stage'] as String?,
    stageLabel: json['stage_label'] as String?,
    stages: (json['stages'] as List)
        .map((s) => CustomOrderStageInfo.fromJson(s as Map<String, dynamic>))
        .toList(),
    adminNote: json['admin_note'] as String?,
    linkedOrder: json['order'] == null ? null : LinkedOrder.fromJson(json['order'] as Map<String, dynamic>),
    items: (json['items'] as List)
        .map((i) => CustomOrderItem.fromJson(i as Map<String, dynamic>))
        .toList(),
    createdAt: DateTime.parse(json['created_at'] as String),
  );

  final int id;
  final String status;
  final String statusLabel;
  final String? stage;
  final String? stageLabel;
  final List<CustomOrderStageInfo> stages;
  final String? adminNote;
  final LinkedOrder? linkedOrder;
  final List<CustomOrderItem> items;
  final DateTime createdAt;

  bool get isRejected => status == 'rejetee';
}

class NewCustomOrderItem {
  const NewCustomOrderItem({
    this.boutiqueId,
    required this.productUrl,
    required this.quantity,
    required this.estimatedPriceEur,
    this.notes,
  });

  final int? boutiqueId;
  final String productUrl;
  final int quantity;
  final double estimatedPriceEur;
  final String? notes;

  Map<String, dynamic> toJson() => {
    if (boutiqueId != null) 'boutique_id': boutiqueId,
    'product_url': productUrl,
    'quantity': quantity,
    'estimated_price_eur': estimatedPriceEur,
    if (notes != null && notes!.isNotEmpty) 'notes': notes,
  };
}
