class Address {
  const Address({
    required this.id,
    required this.label,
    required this.city,
    this.area,
    this.phone,
    required this.isDefault,
  });

  factory Address.fromJson(Map<String, dynamic> json) => Address(
    id: json['id'] as int,
    label: json['label'] as String,
    city: json['city'] as String,
    area: json['area'] as String?,
    phone: json['phone'] as String?,
    isDefault: json['is_default'] as bool,
  );

  final int id;
  final String label;
  final String city;
  final String? area;
  final String? phone;
  final bool isDefault;
}

class NotificationPreferences {
  const NotificationPreferences({required this.push, required this.sms, required this.email});

  factory NotificationPreferences.fromJson(Map<String, dynamic> json) => NotificationPreferences(
    push: json['push'] as bool? ?? true,
    sms: json['sms'] as bool? ?? true,
    email: json['email'] as bool? ?? true,
  );

  final bool push;
  final bool sms;
  final bool email;
}
