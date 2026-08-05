import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../../core/network/api_exception.dart';
import '../../../l10n/generated/app_localizations.dart';
import '../../auth/application/auth_controller.dart';
import '../application/account_providers.dart';
import '../data/account_models.dart';
import '../data/account_repository.dart';

class AccountPage extends ConsumerWidget {
  const AccountPage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    final authState = ref.watch(authControllerProvider);

    return Scaffold(
      appBar: AppBar(title: Text(l10n.navAccount)),
      body: authState.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (error, _) => Center(child: Text(apiErrorMessage(error))),
        data: (user) {
          if (user == null) {
            return Center(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(l10n.accountNotConnected),
                  const SizedBox(height: 12),
                  FilledButton(
                    onPressed: () => context.push('/login'),
                    child: Text(l10n.accountLogin),
                  ),
                ],
              ),
            );
          }

          return ListView(
            children: [
              ListTile(
                leading: const CircleAvatar(child: Icon(Icons.person)),
                title: Text(user.name),
                subtitle: Text(user.phone ?? user.email ?? ''),
              ),
              const Divider(),
              const _AddressesSection(),
              const Divider(),
              const _NotificationPreferencesSection(),
              const Divider(),
              ListTile(
                leading: const Icon(Icons.logout),
                title: Text(l10n.accountLogout),
                onTap: () => ref.read(authControllerProvider.notifier).logout(),
              ),
            ],
          );
        },
      ),
    );
  }
}

class _AddressesSection extends ConsumerWidget {
  const _AddressesSection();

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    final addressesAsync = ref.watch(addressesProvider);

    return Padding(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(l10n.accountAddresses, style: Theme.of(context).textTheme.titleMedium),
              IconButton(
                icon: const Icon(Icons.add_circle_outline),
                onPressed: () => _showAddAddressSheet(context, ref),
              ),
            ],
          ),
          addressesAsync.when(
            data: (addresses) {
              if (addresses.isEmpty) {
                return Padding(
                  padding: const EdgeInsets.symmetric(vertical: 8),
                  child: Text(l10n.accountAddressEmpty, style: Theme.of(context).textTheme.bodySmall),
                );
              }
              return Column(
                children: [
                  for (final address in addresses)
                    ListTile(
                      contentPadding: EdgeInsets.zero,
                      leading: Icon(address.isDefault ? Icons.star : Icons.location_on_outlined),
                      title: Text(address.label),
                      subtitle: Text([address.city, address.area].whereType<String>().join(', ')),
                      trailing: IconButton(
                        icon: const Icon(Icons.delete_outline),
                        onPressed: () async {
                          await ref.read(accountRepositoryProvider).deleteAddress(address.id);
                          ref.invalidate(addressesProvider);
                        },
                      ),
                    ),
                ],
              );
            },
            loading: () => const Padding(
              padding: EdgeInsets.symmetric(vertical: 8),
              child: LinearProgressIndicator(),
            ),
            error: (error, _) => Text(apiErrorMessage(error)),
          ),
        ],
      ),
    );
  }

  void _showAddAddressSheet(BuildContext context, WidgetRef ref) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      builder: (context) => _AddAddressSheet(ref: ref),
    );
  }
}

class _AddAddressSheet extends StatefulWidget {
  const _AddAddressSheet({required this.ref});

  final WidgetRef ref;

  @override
  State<_AddAddressSheet> createState() => _AddAddressSheetState();
}

class _AddAddressSheetState extends State<_AddAddressSheet> {
  final _labelController = TextEditingController();
  final _cityController = TextEditingController();
  final _areaController = TextEditingController();
  final _phoneController = TextEditingController();
  bool _isDefault = false;
  bool _isSubmitting = false;
  String? _error;

  @override
  void dispose() {
    _labelController.dispose();
    _cityController.dispose();
    _areaController.dispose();
    _phoneController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    setState(() {
      _isSubmitting = true;
      _error = null;
    });
    try {
      await widget.ref
          .read(accountRepositoryProvider)
          .createAddress(
            label: _labelController.text.trim(),
            city: _cityController.text.trim(),
            area: _areaController.text.trim(),
            phone: _phoneController.text.trim(),
            isDefault: _isDefault,
          );
      widget.ref.invalidate(addressesProvider);
      if (mounted) Navigator.of(context).pop();
    } catch (e) {
      setState(() => _error = apiErrorMessage(e));
    } finally {
      if (mounted) setState(() => _isSubmitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);

    return Padding(
      padding: EdgeInsets.only(
        left: 16,
        right: 16,
        top: 16,
        bottom: MediaQuery.of(context).viewInsets.bottom + 16,
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Text(l10n.accountAddAddress, style: Theme.of(context).textTheme.titleMedium),
          const SizedBox(height: 12),
          TextField(
            controller: _labelController,
            decoration: InputDecoration(labelText: l10n.accountAddressLabel),
          ),
          TextField(
            controller: _cityController,
            decoration: InputDecoration(labelText: l10n.accountAddressCity),
          ),
          TextField(
            controller: _areaController,
            decoration: InputDecoration(labelText: l10n.accountAddressArea),
          ),
          TextField(
            controller: _phoneController,
            keyboardType: TextInputType.phone,
            decoration: InputDecoration(labelText: l10n.accountAddressPhone),
          ),
          CheckboxListTile(
            contentPadding: EdgeInsets.zero,
            value: _isDefault,
            onChanged: (value) => setState(() => _isDefault = value ?? false),
            title: Text(l10n.accountAddressDefault),
          ),
          if (_error != null) ...[
            Text(_error!, style: TextStyle(color: Theme.of(context).colorScheme.error)),
            const SizedBox(height: 8),
          ],
          FilledButton(
            onPressed: _isSubmitting ? null : _submit,
            child: Text(l10n.accountSave),
          ),
        ],
      ),
    );
  }
}

class _NotificationPreferencesSection extends ConsumerWidget {
  const _NotificationPreferencesSection();

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    final prefsAsync = ref.watch(notificationPreferencesProvider);

    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Padding(
            padding: const EdgeInsets.symmetric(vertical: 8),
            child: Text(l10n.accountNotifications, style: Theme.of(context).textTheme.titleMedium),
          ),
          prefsAsync.when(
            data: (prefs) => _PreferenceSwitches(prefs: prefs),
            loading: () => const LinearProgressIndicator(),
            error: (error, _) => Text(apiErrorMessage(error)),
          ),
        ],
      ),
    );
  }
}

class _PreferenceSwitches extends ConsumerWidget {
  const _PreferenceSwitches({required this.prefs});

  final NotificationPreferences prefs;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);

    Future<void> update({bool? push, bool? sms, bool? email}) async {
      await ref.read(accountRepositoryProvider).updateNotificationPreferences(push: push, sms: sms, email: email);
      ref.invalidate(notificationPreferencesProvider);
    }

    return Column(
      children: [
        SwitchListTile(
          contentPadding: EdgeInsets.zero,
          title: Text(l10n.accountNotifPush),
          value: prefs.push,
          onChanged: (value) => update(push: value),
        ),
        SwitchListTile(
          contentPadding: EdgeInsets.zero,
          title: Text(l10n.accountNotifSms),
          value: prefs.sms,
          onChanged: (value) => update(sms: value),
        ),
        SwitchListTile(
          contentPadding: EdgeInsets.zero,
          title: Text(l10n.accountNotifEmail),
          value: prefs.email,
          onChanged: (value) => update(email: value),
        ),
      ],
    );
  }
}
