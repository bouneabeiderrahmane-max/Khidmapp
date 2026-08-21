/// Poids de colis choisi par le client lui-même au moment du paiement (sur
/// le modèle de l'app de référence "achat par proxy" dont des captures ont
/// été fournies) — une estimation déclarative, pas un poids mesuré. Les
/// clés et tarifs MRU doivent rester synchronisés avec
/// `App\Support\PackageWeightTier` côté backend (même principe que les
/// valeurs de mode de paiement déjà codées en dur des deux côtés).
enum WeightTierOption {
  petit('petit', 600, 0, 4),
  moyen('moyen', 1200, 4, 7),
  tresGrand('tres_grand', 1700, 7, 15);

  const WeightTierOption(this.key, this.feeMru, this.minKg, this.maxKg);

  /// Surcharge par kg au-delà de 15 kg sur le plus grand palier — doit
  /// rester synchronisée avec PackageWeightTier::EXTRA_KG_FEE_MRU côté backend.
  static const double extraKgFeeMru = 200.0;

  final String key;
  final double feeMru;
  final int minKg;
  final int maxKg;

  /// Seul le palier le plus grand accepte un poids supplémentaire au-delà
  /// de 15 kg, facturé 200 MRU/kg côté backend.
  bool get acceptsExtraWeight => this == WeightTierOption.tresGrand;
}
