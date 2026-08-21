import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:webview_flutter/webview_flutter.dart';
import '../../../l10n/generated/app_localizations.dart';
import '../data/home_repository.dart';

/// Affiche le vrai site de la boutique dans un navigateur intégré à
/// l'application (plutôt que le navigateur externe du téléphone, sur
/// demande explicite de l'utilisateur — "tout doit rester dans l'app,
/// comme les autres apps de ce type") : Khidmapp ne synchronise toujours
/// pas réellement de catalogue (voir StubCatalogFetcher), le client
/// navigue donc directement sur le site d'origine, sans en quitter
/// l'application. Un bouton flottant reste accessible vers "Commande
/// personnalisée" pour que le client puisse y revenir facilement une fois
/// le produit trouvé.
class BoutiqueWebViewPage extends StatefulWidget {
  const BoutiqueWebViewPage({super.key, required this.boutique});

  final BoutiqueSummary boutique;

  @override
  State<BoutiqueWebViewPage> createState() => _BoutiqueWebViewPageState();
}

class _BoutiqueWebViewPageState extends State<BoutiqueWebViewPage> {
  late final WebViewController _controller;
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _controller = WebViewController()
      ..setJavaScriptMode(JavaScriptMode.unrestricted)
      ..setNavigationDelegate(
        NavigationDelegate(
          onPageStarted: (_) => setState(() => _isLoading = true),
          onPageFinished: (_) => setState(() => _isLoading = false),
        ),
      )
      ..loadRequest(Uri.parse(widget.boutique.baseUrl));
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
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => context.push('/custom-order/new'),
        icon: const Icon(Icons.add_link),
        label: Text(l10n.homeCustomOrderCta),
      ),
    );
  }
}
