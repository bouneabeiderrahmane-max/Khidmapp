import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/network/api_exception.dart';
import '../../../l10n/generated/app_localizations.dart';
import '../application/support_providers.dart';
import '../data/support_models.dart';
import '../data/support_repository.dart';

class ComplaintDetailPage extends ConsumerStatefulWidget {
  const ComplaintDetailPage({super.key, required this.complaintId});

  final int complaintId;

  @override
  ConsumerState<ComplaintDetailPage> createState() => _ComplaintDetailPageState();
}

class _ComplaintDetailPageState extends ConsumerState<ComplaintDetailPage> {
  final _replyController = TextEditingController();
  bool _isSending = false;

  @override
  void dispose() {
    _replyController.dispose();
    super.dispose();
  }

  Future<void> _sendReply() async {
    final message = _replyController.text.trim();
    if (message.isEmpty) return;

    setState(() => _isSending = true);
    try {
      await ref.read(supportRepositoryProvider).addMessage(widget.complaintId, message);
      _replyController.clear();
      ref.invalidate(complaintDetailProvider(widget.complaintId));
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(apiErrorMessage(e))));
      }
    } finally {
      if (mounted) setState(() => _isSending = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final complaintAsync = ref.watch(complaintDetailProvider(widget.complaintId));

    return Scaffold(
      appBar: AppBar(title: Text(l10n.supportComplaints)),
      body: complaintAsync.when(
        data: (complaint) => Column(
          children: [
            ListTile(
              title: Text(complaint.categoryLabel),
              subtitle: Text('${complaint.statusLabel} · #${complaint.orderId}'),
            ),
            const Divider(height: 1),
            Expanded(
              child: ListView(
                padding: const EdgeInsets.all(12),
                children: [for (final message in complaint.messages) _MessageBubble(message: message)],
              ),
            ),
            SafeArea(
              child: Padding(
                padding: const EdgeInsets.all(8),
                child: Row(
                  children: [
                    Expanded(
                      child: TextField(
                        controller: _replyController,
                        decoration: InputDecoration(
                          hintText: l10n.supportComplaintReply,
                          border: OutlineInputBorder(borderRadius: BorderRadius.circular(24)),
                          contentPadding: const EdgeInsets.symmetric(horizontal: 16),
                        ),
                      ),
                    ),
                    IconButton(
                      icon: const Icon(Icons.send),
                      onPressed: _isSending ? null : _sendReply,
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (error, _) => Center(child: Text(apiErrorMessage(error))),
      ),
    );
  }
}

class _MessageBubble extends StatelessWidget {
  const _MessageBubble({required this.message});

  final ComplaintMessage message;

  @override
  Widget build(BuildContext context) {
    final isClient = message.senderType == 'client';

    return Align(
      alignment: isClient ? AlignmentDirectional.centerEnd : AlignmentDirectional.centerStart,
      child: Container(
        margin: const EdgeInsets.symmetric(vertical: 4),
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
        constraints: BoxConstraints(maxWidth: MediaQuery.of(context).size.width * 0.75),
        decoration: BoxDecoration(
          color: isClient
              ? Theme.of(context).colorScheme.primaryContainer
              : Theme.of(context).colorScheme.surfaceContainerHighest,
          borderRadius: BorderRadius.circular(12),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            if (message.senderName != null)
              Text(message.senderName!, style: Theme.of(context).textTheme.labelSmall),
            if (message.message != null) Text(message.message!),
          ],
        ),
      ),
    );
  }
}
