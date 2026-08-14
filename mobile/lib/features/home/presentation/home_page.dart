import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../../core/network/api_exception.dart';
import '../../../l10n/generated/app_localizations.dart';
import '../../auth/application/auth_controller.dart';
import '../../catalog/application/catalog_providers.dart';
import '../application/home_providers.dart';
import '../data/home_repository.dart';

class HomePage extends ConsumerStatefulWidget {
  const HomePage({super.key});

  @override
  ConsumerState<HomePage> createState() => _HomePageState();
}

class _HomePageState extends ConsumerState<HomePage> {
  final _searchController = TextEditingController();

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  void _openBoutique(BoutiqueSummary boutique) {
    ref.read(catalogFiltersProvider.notifier).state = ref
        .read(catalogFiltersProvider)
        .copyWith(boutiqueSlug: boutique.slug, page: 1);
    context.push('/catalog');
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final authState = ref.watch(authControllerProvider);
    final boutiquesAsync = ref.watch(filteredBoutiquesProvider);

    return Scaffold(
      appBar: AppBar(title: Text(l10n.navHome)),
      body: RefreshIndicator(
        onRefresh: () => ref.refresh(boutiquesProvider.future),
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            Text(
              authState.valueOrNull != null
                  ? l10n.homeWelcome(authState.valueOrNull!.name)
                  : l10n.homeWelcomeGuest,
              style: Theme.of(context).textTheme.titleLarge,
            ),
            const SizedBox(height: 4),
            Text(l10n.homeWhatToBuy, style: Theme.of(context).textTheme.bodyMedium),
            const SizedBox(height: 16),
            TextField(
              controller: _searchController,
              decoration: InputDecoration(
                hintText: l10n.homeSearchBoutique,
                prefixIcon: const Icon(Icons.search),
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(24)),
                isDense: true,
              ),
              onChanged: (value) => ref.read(boutiqueSearchProvider.notifier).state = value,
            ),
            const SizedBox(height: 16),
            _CustomOrderCard(l10n: l10n),
            const SizedBox(height: 8),
            if (authState.valueOrNull != null)
              Align(
                alignment: AlignmentDirectional.centerStart,
                child: TextButton(
                  onPressed: () => context.push('/custom-order'),
                  child: Text('${l10n.homeMyCustomOrders} · ${l10n.homeSeeAll}'),
                ),
              ),
            const SizedBox(height: 16),
            Text(l10n.homeBoutiquesTitle, style: Theme.of(context).textTheme.titleMedium),
            const SizedBox(height: 12),
            boutiquesAsync.when(
              data: (boutiques) {
                if (boutiques.isEmpty) {
                  return Padding(
                    padding: const EdgeInsets.symmetric(vertical: 24),
                    child: Center(child: Text(l10n.homeBoutiquesEmpty)),
                  );
                }
                return GridView.builder(
                  shrinkWrap: true,
                  physics: const NeverScrollableScrollPhysics(),
                  gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                    crossAxisCount: 3,
                    childAspectRatio: 0.85,
                    crossAxisSpacing: 12,
                    mainAxisSpacing: 12,
                  ),
                  itemCount: boutiques.length,
                  itemBuilder: (context, index) =>
                      _BoutiqueCard(boutique: boutiques[index], onTap: () => _openBoutique(boutiques[index])),
                );
              },
              loading: () => const Padding(
                padding: EdgeInsets.symmetric(vertical: 24),
                child: Center(child: CircularProgressIndicator()),
              ),
              error: (error, _) => Padding(
                padding: const EdgeInsets.symmetric(vertical: 24),
                child: Center(child: Text(apiErrorMessage(error))),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _CustomOrderCard extends StatelessWidget {
  const _CustomOrderCard({required this.l10n});

  final AppLocalizations l10n;

  @override
  Widget build(BuildContext context) {
    return Card(
      color: Theme.of(context).colorScheme.primaryContainer,
      child: InkWell(
        onTap: () => context.push('/custom-order/new'),
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Row(
            children: [
              Icon(Icons.add_link, color: Theme.of(context).colorScheme.onPrimaryContainer),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      l10n.homeCustomOrderCta,
                      style: Theme.of(context).textTheme.titleMedium?.copyWith(
                        color: Theme.of(context).colorScheme.onPrimaryContainer,
                      ),
                    ),
                    Text(
                      l10n.homeCustomOrderSubtitle,
                      style: Theme.of(context).textTheme.bodySmall?.copyWith(
                        color: Theme.of(context).colorScheme.onPrimaryContainer,
                      ),
                    ),
                  ],
                ),
              ),
              Icon(Icons.chevron_right, color: Theme.of(context).colorScheme.onPrimaryContainer),
            ],
          ),
        ),
      ),
    );
  }
}

class _BoutiqueCard extends StatelessWidget {
  const _BoutiqueCard({required this.boutique, required this.onTap});

  final BoutiqueSummary boutique;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Card(
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: onTap,
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Expanded(
              child: boutique.logoUrl != null
                  ? Image.network(
                      boutique.logoUrl!,
                      fit: BoxFit.contain,
                      errorBuilder: (context, _, __) => const _BoutiquePlaceholder(),
                    )
                  : const _BoutiquePlaceholder(),
            ),
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 4),
              child: Text(
                boutique.name,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                textAlign: TextAlign.center,
                style: Theme.of(context).textTheme.bodySmall,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _BoutiquePlaceholder extends StatelessWidget {
  const _BoutiquePlaceholder();

  @override
  Widget build(BuildContext context) {
    return Container(
      color: Theme.of(context).colorScheme.surfaceContainerHighest,
      child: Icon(Icons.storefront_outlined, color: Theme.of(context).colorScheme.outline),
    );
  }
}
