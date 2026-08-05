import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'core/config/locale_provider.dart';
import 'core/theme/app_theme.dart';
import 'l10n/generated/app_localizations.dart';
import 'routing/app_router.dart';

class KhidmappApp extends ConsumerWidget {
  const KhidmappApp({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final locale = ref.watch(localeProvider);
    final router = ref.watch(routerProvider);

    return MaterialApp.router(
      onGenerateTitle: (context) => AppLocalizations.of(context).appName,
      theme: AppTheme.light(),
      routerConfig: router,
      locale: locale,
      supportedLocales: AppLocalizations.supportedLocales,
      localizationsDelegates: const [
        AppLocalizations.delegate,
        GlobalMaterialLocalizations.delegate,
        GlobalWidgetsLocalizations.delegate,
        GlobalCupertinoLocalizations.delegate,
      ],
    );
  }
}
