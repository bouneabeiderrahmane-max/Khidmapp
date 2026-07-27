import 'package:go_router/go_router.dart';
import '../features/placeholder/placeholder_page.dart';
import '../features/shell/home_shell.dart';

final appRouter = GoRouter(
  initialLocation: '/catalog',
  routes: [
    StatefulShellRoute.indexedStack(
      builder: (context, state, navigationShell) =>
          HomeShell(navigationShell: navigationShell),
      branches: [
        StatefulShellBranch(
          routes: [
            GoRoute(
              path: '/catalog',
              builder: (context, state) =>
                  const PlaceholderPage(title: 'Catalogue'),
            ),
          ],
        ),
        StatefulShellBranch(
          routes: [
            GoRoute(
              path: '/cart',
              builder: (context, state) =>
                  const PlaceholderPage(title: 'Panier'),
            ),
          ],
        ),
        StatefulShellBranch(
          routes: [
            GoRoute(
              path: '/orders',
              builder: (context, state) =>
                  const PlaceholderPage(title: 'Commandes'),
            ),
          ],
        ),
        StatefulShellBranch(
          routes: [
            GoRoute(
              path: '/support',
              builder: (context, state) =>
                  const PlaceholderPage(title: 'Support'),
            ),
          ],
        ),
        StatefulShellBranch(
          routes: [
            GoRoute(
              path: '/account',
              builder: (context, state) =>
                  const PlaceholderPage(title: 'Compte'),
            ),
          ],
        ),
      ],
    ),
  ],
);
