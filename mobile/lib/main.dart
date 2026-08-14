import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'app.dart';
import 'core/config/dev_settings.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  final initialApiBaseUrl = await loadInitialApiBaseUrl();

  runApp(
    ProviderScope(
      overrides: [apiBaseUrlProvider.overrideWith((ref) => initialApiBaseUrl)],
      child: const KhidmappApp(),
    ),
  );
}
