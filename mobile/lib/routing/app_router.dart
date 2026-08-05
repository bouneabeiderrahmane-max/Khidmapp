import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../features/auth/presentation/login_page.dart';
import '../features/catalog/presentation/catalog_page.dart';
import '../features/catalog/presentation/product_detail_page.dart';
import '../features/placeholder/placeholder_page.dart';
import '../features/shell/home_shell.dart';

/// Router construit via un Provider (plutôt qu'une constante top-level)
/// pour pouvoir, plus tard, réagir à l'état d'authentification sans
/// dépendance externe à go_router (ex. package go_router_riverpod) :
/// le catalogue reste consultable sans connexion (8.1.2/8.2, catalogue
/// public), seules certaines actions (panier, commande, compte)
/// nécessiteront une session — gérées pour l'instant au niveau de
/// chaque écran plutôt que par une redirection globale.
final routerProvider = Provider<GoRouter>((ref) {
  return GoRouter(
    initialLocation: '/catalog',
    routes: [
      GoRoute(path: '/login', builder: (context, state) => const LoginPage()),
      GoRoute(
        path: '/product/:id',
        builder: (context, state) =>
            ProductDetailPage(productId: int.parse(state.pathParameters['id']!)),
      ),
      StatefulShellRoute.indexedStack(
        builder: (context, state, navigationShell) =>
            HomeShell(navigationShell: navigationShell),
        branches: [
          StatefulShellBranch(
            routes: [GoRoute(path: '/catalog', builder: (context, state) => const CatalogPage())],
          ),
          StatefulShellBranch(
            routes: [
              GoRoute(
                path: '/cart',
                builder: (context, state) => const PlaceholderPage(title: 'Panier'),
              ),
            ],
          ),
          StatefulShellBranch(
            routes: [
              GoRoute(
                path: '/orders',
                builder: (context, state) => const PlaceholderPage(title: 'Commandes'),
              ),
            ],
          ),
          StatefulShellBranch(
            routes: [
              GoRoute(
                path: '/support',
                builder: (context, state) => const PlaceholderPage(title: 'Support'),
              ),
            ],
          ),
          StatefulShellBranch(
            routes: [
              GoRoute(
                path: '/account',
                builder: (context, state) => const PlaceholderPage(title: 'Compte'),
              ),
            ],
          ),
        ],
      ),
    ],
  );
});
