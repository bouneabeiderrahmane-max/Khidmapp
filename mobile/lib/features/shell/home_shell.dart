import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../../l10n/generated/app_localizations.dart';

class HomeShell extends StatelessWidget {
  const HomeShell({super.key, required this.navigationShell});

  final StatefulNavigationShell navigationShell;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);

    return Scaffold(
      body: navigationShell,
      bottomNavigationBar: NavigationBar(
        selectedIndex: navigationShell.currentIndex,
        onDestinationSelected: (index) => navigationShell.goBranch(index),
        destinations: [
          NavigationDestination(
            icon: const Icon(Icons.home_outlined),
            label: l10n.navHome,
          ),
          NavigationDestination(
            icon: const Icon(Icons.storefront_outlined),
            label: l10n.navCatalog,
          ),
          NavigationDestination(
            icon: const Icon(Icons.shopping_cart_outlined),
            label: l10n.navCart,
          ),
          NavigationDestination(
            icon: const Icon(Icons.receipt_long_outlined),
            label: l10n.navOrders,
          ),
          NavigationDestination(
            icon: const Icon(Icons.support_agent_outlined),
            label: l10n.navSupport,
          ),
          NavigationDestination(
            icon: const Icon(Icons.person_outline),
            label: l10n.navAccount,
          ),
        ],
      ),
    );
  }
}
