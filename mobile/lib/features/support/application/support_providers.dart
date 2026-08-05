import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../data/support_models.dart';
import '../data/support_repository.dart';

final faqsProvider = FutureProvider<List<Faq>>((ref) {
  return ref.watch(supportRepositoryProvider).fetchFaqs();
});

final complaintsProvider = FutureProvider<List<Complaint>>((ref) {
  return ref.watch(supportRepositoryProvider).fetchComplaints();
});

final complaintDetailProvider = FutureProvider.family<Complaint, int>((ref, id) {
  return ref.watch(supportRepositoryProvider).fetchComplaint(id);
});
