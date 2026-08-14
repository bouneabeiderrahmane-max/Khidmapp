import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../features/account/presentation/account_page.dart';
import '../features/auth/presentation/login_page.dart';
import '../features/catalog/presentation/catalog_page.dart';
import '../features/catalog/presentation/product_detail_page.dart';
import '../features/custom_order/presentation/custom_order_detail_page.dart';
import '../features/custom_order/presentation/custom_order_list_page.dart';
import '../features/custom_order/presentation/new_custom_order_page.dart';
import '../features/home/presentation/home_page.dart';
import '../features/placeholder/placeholder_page.dart';
import '../features/shell/home_shell.dart';
import '../features/support/presentation/complaint_detail_page.dart';
import '../features/support/presentation/new_complaint_page.dart';
import '../features/support/presentation/support_page.dart';

/// Router construit via un Provider (plutôt qu'une constante top-level)
/// pour pouvoir, plus tard, réagir à l'état d'authentification sans
/// dépendance externe à go_router (ex. package go_router_riverpod) :
/// le catalogue et la FAQ restent consultables sans connexion (8.1.2/8.2,
/// 8.8), seules certaines actions (panier, réclamation, compte)
/// nécessitent une session — gérées pour l'instant au niveau de chaque
/// écran plutôt que par une redirection globale.
final routerProvider = Provider<GoRouter>((ref) {
  return GoRouter(
    initialLocation: '/',
    routes: [
      GoRoute(path: '/login', builder: (context, state) => const LoginPage()),
      GoRoute(
        path: '/product/:id',
        builder: (context, state) =>
            ProductDetailPage(productId: int.parse(state.pathParameters['id']!)),
      ),
      GoRoute(
        path: '/custom-order/new',
        builder: (context, state) => const NewCustomOrderPage(),
      ),
      GoRoute(
        path: '/custom-order',
        builder: (context, state) => const CustomOrderListPage(),
      ),
      GoRoute(
        path: '/custom-order/:id',
        builder: (context, state) =>
            CustomOrderDetailPage(requestId: int.parse(state.pathParameters['id']!)),
      ),
      GoRoute(
        path: '/support/new-complaint',
        builder: (context, state) => const NewComplaintPage(),
      ),
      GoRoute(
        path: '/support/complaints/:id',
        builder: (context, state) =>
            ComplaintDetailPage(complaintId: int.parse(state.pathParameters['id']!)),
      ),
      StatefulShellRoute.indexedStack(
        builder: (context, state, navigationShell) =>
            HomeShell(navigationShell: navigationShell),
        branches: [
          StatefulShellBranch(
            routes: [GoRoute(path: '/', builder: (context, state) => const HomePage())],
          ),
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
            routes: [GoRoute(path: '/support', builder: (context, state) => const SupportPage())],
          ),
          StatefulShellBranch(
            routes: [GoRoute(path: '/account', builder: (context, state) => const AccountPage())],
          ),
        ],
      ),
    ],
  );
});
