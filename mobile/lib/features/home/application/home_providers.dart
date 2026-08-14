import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../data/home_repository.dart';

final boutiquesProvider = FutureProvider<List<BoutiqueSummary>>((ref) {
  return ref.watch(homeRepositoryProvider).fetchBoutiques();
});

final boutiqueSearchProvider = StateProvider<String>((ref) => '');

final filteredBoutiquesProvider = Provider<AsyncValue<List<BoutiqueSummary>>>((ref) {
  final query = ref.watch(boutiqueSearchProvider).trim().toLowerCase();
  final boutiques = ref.watch(boutiquesProvider);

  return boutiques.whenData(
    (list) => query.isEmpty
        ? list
        : list.where((b) => b.name.toLowerCase().contains(query)).toList(),
  );
});
