import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../../core/network/api_exception.dart';
import '../../../l10n/generated/app_localizations.dart';
import '../../auth/application/auth_controller.dart';
import '../application/support_providers.dart';
import '../data/support_models.dart';

class SupportPage extends StatelessWidget {
  const SupportPage({super.key});

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);

    return DefaultTabController(
      length: 2,
      child: Scaffold(
        appBar: AppBar(
          title: Text(l10n.navSupport),
          bottom: TabBar(tabs: [Tab(text: l10n.supportFaq), Tab(text: l10n.supportComplaints)]),
        ),
        body: const TabBarView(children: [_FaqTab(), _ComplaintsTab()]),
      ),
    );
  }
}

class _FaqTab extends ConsumerWidget {
  const _FaqTab();

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final locale = Localizations.localeOf(context).languageCode;
    final faqsAsync = ref.watch(faqsProvider);

    return faqsAsync.when(
      data: (faqs) => ListView(
        children: [
          for (final faq in faqs)
            ExpansionTile(
              title: Text(faq.question.forLocale(locale)),
              children: [
                Padding(
                  padding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
                  child: Align(
                    alignment: AlignmentDirectional.centerStart,
                    child: Text(faq.answer.forLocale(locale)),
                  ),
                ),
              ],
            ),
        ],
      ),
      loading: () => const Center(child: CircularProgressIndicator()),
      error: (error, _) => Center(child: Text(apiErrorMessage(error))),
    );
  }
}

class _ComplaintsTab extends ConsumerWidget {
  const _ComplaintsTab();

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    final isAuthenticated = ref.watch(isAuthenticatedProvider);

    if (!isAuthenticated) {
      return Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(l10n.loginRequired),
            const SizedBox(height: 12),
            FilledButton(onPressed: () => context.push('/login'), child: Text(l10n.accountLogin)),
          ],
        ),
      );
    }

    final complaintsAsync = ref.watch(complaintsProvider);

    return Scaffold(
      body: complaintsAsync.when(
        data: (complaints) {
          if (complaints.isEmpty) {
            return Center(child: Text(l10n.supportComplaintEmpty));
          }
          return ListView(
            children: [
              for (final complaint in complaints) _ComplaintTile(complaint: complaint),
            ],
          );
        },
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (error, _) => Center(child: Text(apiErrorMessage(error))),
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => context.push('/support/new-complaint'),
        icon: const Icon(Icons.add),
        label: Text(l10n.supportNewComplaint),
      ),
    );
  }
}

class _ComplaintTile extends StatelessWidget {
  const _ComplaintTile({required this.complaint});

  final Complaint complaint;

  @override
  Widget build(BuildContext context) {
    return ListTile(
      leading: const Icon(Icons.chat_bubble_outline),
      title: Text(complaint.categoryLabel),
      subtitle: Text('${complaint.statusLabel} · #${complaint.orderId}'),
      onTap: () => context.push('/support/complaints/${complaint.id}'),
    );
  }
}
