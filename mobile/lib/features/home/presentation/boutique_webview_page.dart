import 'dart:async';

import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:webview_flutter/webview_flutter.dart';
import '../../../l10n/generated/app_localizations.dart';
import '../data/home_repository.dart';

/// Script best-effort : parcourt le texte de la page à la recherche d'un
/// montant proche d'un mot-clé de prix — **volontairement large**, pour
/// détecter aussi bien un prix affiché sur une fiche produit (accessible
/// sans connexion) qu'un total de panier, puisque forcer le client à créer
/// un compte sur le site tiers pour atteindre son panier n'a aucun sens
/// dans ce parcours. Aucune garantie de fonctionnement — chaque boutique a
/// sa propre structure de page, jamais documentée ni stable (voir le
/// docblock de BoutiqueWebViewPage) ; en l'absence de correspondance, rien
/// n'est envoyé à Flutter et le client garde l'ancien parcours manuel via
/// le bouton flottant.
const String _priceDetectionScript = '''
(function() {
  function extractPrice(text) {
    var m = text.match(/(\\d{1,5}[.,]\\d{2})\\s*(€|EUR)/i) || text.match(/(€|EUR)\\s*(\\d{1,5}[.,]\\d{2})/i);
    if (!m) return null;
    var raw = /^\\d/.test(m[1]) ? m[1] : m[2];
    var value = parseFloat(raw.replace(',', '.'));
    return isNaN(value) ? null : value;
  }
  var keywords = [
    'subtotal', 'sub-total', 'sous-total', 'total', 'importe', 'montant', 'suma',
    'price', 'precio', 'preço', 'prezzo', 'prix'
  ];
  var candidates = [];
  var elements = document.body ? document.body.getElementsByTagName('*') : [];
  for (var i = 0; i < elements.length; i++) {
    var el = elements[i];
    var text = (el.innerText || '').trim();
    var attrText = ((el.getAttribute && (el.getAttribute('aria-label') || el.className)) || '') + ' ' + text;
    if (!text || text.length > 120) continue;
    var lower = attrText.toLowerCase();
    for (var k = 0; k < keywords.length; k++) {
      if (lower.indexOf(keywords[k]) !== -1) {
        var price = extractPrice(text);
        if (price !== null && price > 0 && price < 100000) candidates.push(price);
        break;
      }
    }
  }
  if (candidates.length === 0) return;
  // Sur une fiche produit, le prix affiché est en général la seule/plus
  // petite valeur pertinente parmi les correspondances (les autres sont
  // souvent des prix barrés, avis, etc.) ; sur un panier, le total est la
  // plus grande. Faute de pouvoir distinguer les deux pages de façon
  // fiable, on retient la valeur la plus fréquente, qui tend à être la
  // bonne dans les deux cas — à défaut la plus grande.
  var counts = {};
  var best = candidates[0];
  var bestCount = 0;
  for (var c = 0; c < candidates.length; c++) {
    var key = candidates[c].toFixed(2);
    counts[key] = (counts[key] || 0) + 1;
    if (counts[key] > bestCount) {
      bestCount = counts[key];
      best = candidates[c];
    }
  }
  KhidmappPriceDetector.postMessage(String(best));
})();
''';

/// Affiche le vrai site de la boutique dans un navigateur intégré à
/// l'application (plutôt que le navigateur externe du téléphone, sur
/// demande explicite de l'utilisateur — "tout doit rester dans l'app,
/// comme les autres apps de ce type") : Khidmapp ne synchronise toujours
/// pas réellement de catalogue (voir StubCatalogFetcher), le client
/// navigue donc directement sur le site d'origine, sans en quitter
/// l'application.
///
/// Sur le modèle d'une app tierce de référence (captures d'écran
/// fournies par l'utilisateur) : une bannière apparaît en bas de l'écran
/// dès qu'un montant plausible est détecté sur la page courante (méthode
/// heuristique, `_priceDetectionScript` — **jamais garantie**, chaque site
/// ayant sa propre structure). Le client vérifie ce montant puis
/// "Continuer avec Khidmapp" pré-remplit le formulaire de commande
/// personnalisée (lien + prix) — il reste à vérifier/envoyer, comme pour
/// toute demande personnalisée ; Khidmapp n'achète jamais automatiquement,
/// ni n'intercepte le vrai paiement du site tiers. Sans détection, le
/// bouton flottant "Commande personnalisée" (lien vide, à coller
/// manuellement) reste la solution de repli.
class BoutiqueWebViewPage extends StatefulWidget {
  const BoutiqueWebViewPage({super.key, required this.boutique});

  final BoutiqueSummary boutique;

  @override
  State<BoutiqueWebViewPage> createState() => _BoutiqueWebViewPageState();
}

class _BoutiqueWebViewPageState extends State<BoutiqueWebViewPage> {
  static const _rescanInterval = Duration(seconds: 2);
  static const _rescanTotalDuration = Duration(seconds: 20);

  late final WebViewController _controller;
  bool _isLoading = true;
  double? _detectedPriceEur;
  String? _detectedPageUrl;
  Timer? _rescanTimer;

  @override
  void initState() {
    super.initState();
    _controller = WebViewController()
      ..setJavaScriptMode(JavaScriptMode.unrestricted)
      ..addJavaScriptChannel(
        'KhidmappPriceDetector',
        onMessageReceived: (message) {
          final price = double.tryParse(message.message);
          if (price == null || !mounted) return;
          setState(() {
            _detectedPriceEur = price;
            _detectedPageUrl = null; // renseigné juste après via currentUrl().
          });
          _controller.currentUrl().then((url) {
            if (mounted) setState(() => _detectedPageUrl = url);
          });
        },
      )
      ..setNavigationDelegate(
        NavigationDelegate(
          onPageStarted: (_) => setState(() {
            _isLoading = true;
            // Une nouvelle page peut ne plus correspondre au montant détecté
            // sur la précédente — on efface plutôt que d'afficher un prix
            // obsolète.
            _detectedPriceEur = null;
            _detectedPageUrl = null;
          }),
          onPageFinished: (_) {
            setState(() => _isLoading = false);
            _startRescanLoop();
          },
        ),
      )
      ..loadRequest(Uri.parse(widget.boutique.baseUrl));
  }

  /// Relance le script de détection toutes les 2 s pendant 20 s après
  /// chaque navigation, plutôt qu'une seule fois : beaucoup de sites
  /// e-commerce sont des applications JS (SPA) dont le contenu se charge
  /// après l'évènement "page terminée", et changent parfois de "page"
  /// (produit → panier) sans déclencher de nouvelle navigation complète
  /// que le WebView puisse détecter — un ré-examen répété est le seul
  /// moyen fiable d'attraper ces deux cas sans dépendre de la structure
  /// propre à chaque site.
  void _startRescanLoop() {
    _rescanTimer?.cancel();
    var elapsed = Duration.zero;
    _rescanTimer = Timer.periodic(_rescanInterval, (timer) {
      if (!mounted || elapsed >= _rescanTotalDuration) {
        timer.cancel();
        return;
      }
      elapsed += _rescanInterval;
      _controller.runJavaScript(_priceDetectionScript);
    });
  }

  @override
  void dispose() {
    _rescanTimer?.cancel();
    super.dispose();
  }

  void _continueWithKhidmapp() {
    context.push(
      '/custom-order/new',
      extra: (productUrl: _detectedPageUrl, priceEur: _detectedPriceEur),
    );
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);

    return Scaffold(
      appBar: AppBar(
        title: Text(widget.boutique.name),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: () => _controller.reload(),
          ),
        ],
      ),
      body: Stack(
        children: [
          WebViewWidget(controller: _controller),
          if (_isLoading) const LinearProgressIndicator(),
        ],
      ),
      bottomNavigationBar: _detectedPriceEur != null
          ? _DetectedPriceBanner(priceEur: _detectedPriceEur!, onContinue: _continueWithKhidmapp)
          : null,
      floatingActionButton: _detectedPriceEur == null
          ? FloatingActionButton.extended(
              onPressed: () => context.push('/custom-order/new'),
              icon: const Icon(Icons.add_link),
              label: Text(l10n.homeCustomOrderCta),
            )
          : null,
    );
  }
}

class _DetectedPriceBanner extends StatelessWidget {
  const _DetectedPriceBanner({required this.priceEur, required this.onContinue});

  final double priceEur;
  final VoidCallback onContinue;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final colorScheme = Theme.of(context).colorScheme;

    return SafeArea(
      child: Container(
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: colorScheme.primaryContainer,
          border: Border(top: BorderSide(color: colorScheme.outlineVariant)),
        ),
        child: Row(
          children: [
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    l10n.boutiqueDetectedPrice(priceEur.toStringAsFixed(2)),
                    style: Theme.of(context).textTheme.titleSmall?.copyWith(
                      color: colorScheme.onPrimaryContainer,
                    ),
                  ),
                  Text(
                    l10n.boutiqueDetectedPriceHint,
                    style: Theme.of(context).textTheme.bodySmall?.copyWith(
                      color: colorScheme.onPrimaryContainer,
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(width: 8),
            FilledButton(onPressed: onContinue, child: Text(l10n.boutiqueContinueWithKhidmapp)),
          ],
        ),
      ),
    );
  }
}
