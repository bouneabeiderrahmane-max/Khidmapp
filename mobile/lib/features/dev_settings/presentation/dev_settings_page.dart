import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/config/dev_settings.dart';
import '../../../l10n/generated/app_localizations.dart';

/// Écran debug uniquement (jamais accessible en release, voir
/// AccountPage — le point d'entrée est masqué par `kDebugMode`) : permet
/// de changer l'URL de base de l'API directement depuis le téléphone,
/// sans recompiler l'APK à chaque changement d'IP Wi-Fi locale.
class DevSettingsPage extends ConsumerStatefulWidget {
  const DevSettingsPage({super.key});

  @override
  ConsumerState<DevSettingsPage> createState() => _DevSettingsPageState();
}

class _DevSettingsPageState extends ConsumerState<DevSettingsPage> {
  late final TextEditingController _urlController;
  String? _error;
  String? _confirmation;

  @override
  void initState() {
    super.initState();
    _urlController = TextEditingController(text: ref.read(apiBaseUrlProvider));
  }

  @override
  void dispose() {
    _urlController.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    final url = _urlController.text.trim();
    final l10n = AppLocalizations.of(context);

    if (!url.startsWith('http://') && !url.startsWith('https://')) {
      setState(() {
        _error = l10n.devSettingsInvalidUrl;
        _confirmation = null;
      });
      return;
    }

    final normalized = url.endsWith('/') ? url.substring(0, url.length - 1) : url;

    await ref.read(devSettingsRepositoryProvider).saveApiBaseUrl(normalized);
    ref.read(apiBaseUrlProvider.notifier).state = normalized;

    setState(() {
      _error = null;
      _confirmation = l10n.devSettingsSaved;
    });
  }

  Future<void> _reset() async {
    await ref.read(devSettingsRepositoryProvider).clearApiBaseUrl();
    final defaultUrl = await loadInitialApiBaseUrl();
    ref.read(apiBaseUrlProvider.notifier).state = defaultUrl;

    setState(() {
      _urlController.text = defaultUrl;
      _error = null;
      _confirmation = AppLocalizations.of(context).devSettingsSaved;
    });
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final currentUrl = ref.watch(apiBaseUrlProvider);

    return Scaffold(
      appBar: AppBar(title: Text(l10n.devSettingsTitle)),
      body: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Text(
              l10n.devSettingsCurrentlyUsed(currentUrl),
              style: Theme.of(context).textTheme.bodySmall?.copyWith(
                color: Theme.of(context).colorScheme.outline,
              ),
            ),
            const SizedBox(height: 16),
            TextField(
              controller: _urlController,
              keyboardType: TextInputType.url,
              decoration: InputDecoration(
                labelText: l10n.devSettingsApiBaseUrl,
                hintText: l10n.devSettingsHint,
                border: const OutlineInputBorder(),
              ),
            ),
            if (_error != null) ...[
              const SizedBox(height: 8),
              Text(_error!, style: TextStyle(color: Theme.of(context).colorScheme.error)),
            ],
            if (_confirmation != null) ...[
              const SizedBox(height: 8),
              Text(_confirmation!, style: TextStyle(color: Theme.of(context).colorScheme.primary)),
            ],
            const SizedBox(height: 16),
            FilledButton(onPressed: _save, child: Text(l10n.devSettingsSave)),
            const SizedBox(height: 8),
            OutlinedButton(onPressed: _reset, child: Text(l10n.devSettingsReset)),
          ],
        ),
      ),
    );
  }
}
