import '../../../core/models/localized_text.dart';

class Faq {
  const Faq({required this.id, required this.question, required this.answer, this.category});

  factory Faq.fromJson(Map<String, dynamic> json) => Faq(
    id: json['id'] as int,
    question: LocalizedText.fromJson(json['question'] as Map<String, dynamic>),
    answer: LocalizedText.fromJson(json['answer'] as Map<String, dynamic>),
    category: json['category'] as String?,
  );

  final int id;
  final LocalizedText question;
  final LocalizedText answer;
  final String? category;
}

class ComplaintMessage {
  const ComplaintMessage({
    required this.id,
    required this.senderType,
    this.senderName,
    this.message,
    required this.attachmentUrls,
    required this.createdAt,
  });

  factory ComplaintMessage.fromJson(Map<String, dynamic> json) => ComplaintMessage(
    id: json['id'] as int,
    senderType: json['sender_type'] as String,
    senderName: json['sender_name'] as String?,
    message: json['message'] as String?,
    attachmentUrls: (json['attachment_urls'] as List).map((e) => e.toString()).toList(),
    createdAt: DateTime.parse(json['created_at'] as String),
  );

  final int id;
  final String senderType;
  final String? senderName;
  final String? message;
  final List<String> attachmentUrls;
  final DateTime createdAt;
}

class Complaint {
  const Complaint({
    required this.id,
    required this.orderId,
    required this.category,
    required this.categoryLabel,
    required this.status,
    required this.statusLabel,
    required this.messages,
    required this.createdAt,
  });

  factory Complaint.fromJson(Map<String, dynamic> json) => Complaint(
    id: json['id'] as int,
    orderId: json['order_id'] as int,
    category: json['category'] as String,
    categoryLabel: json['category_label'] as String,
    status: json['status'] as String,
    statusLabel: json['status_label'] as String,
    messages: json['messages'] == null
        ? const []
        : (json['messages'] as List).map((m) => ComplaintMessage.fromJson(m as Map<String, dynamic>)).toList(),
    createdAt: DateTime.parse(json['created_at'] as String),
  );

  final int id;
  final int orderId;
  final String category;
  final String categoryLabel;
  final String status;
  final String statusLabel;
  final List<ComplaintMessage> messages;
  final DateTime createdAt;
}

const complaintCategories = [
  'produit_non_conforme',
  'retard',
  'dommage',
  'erreur_facturation',
  'autre',
];
