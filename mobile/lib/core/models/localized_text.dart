/// Champ bilingue tel que renvoyé par l'API (`{"fr": "...", "ar": "..."}`).
class LocalizedText {
  const LocalizedText(this.values);

  factory LocalizedText.fromJson(Map<String, dynamic> json) =>
      LocalizedText(json.map((key, value) => MapEntry(key, value.toString())));

  final Map<String, String> values;

  String forLocale(String locale) => values[locale] ?? values.values.firstOrNull ?? '';
}

extension FirstOrNullExtension<T> on Iterable<T> {
  T? get firstOrNull => isEmpty ? null : first;
}
