import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../../core/network/api_exception.dart';
import '../../../l10n/generated/app_localizations.dart';
import '../application/auth_controller.dart';

class LoginPage extends ConsumerStatefulWidget {
  const LoginPage({super.key});

  @override
  ConsumerState<LoginPage> createState() => _LoginPageState();
}

class _LoginPageState extends ConsumerState<LoginPage> {
  final _phoneController = TextEditingController();
  final _codeController = TextEditingController();
  bool _otpSent = false;
  bool _isSubmitting = false;
  String? _error;

  @override
  void dispose() {
    _phoneController.dispose();
    _codeController.dispose();
    super.dispose();
  }

  Future<void> _requestOtp() async {
    setState(() {
      _isSubmitting = true;
      _error = null;
    });
    try {
      await ref.read(authControllerProvider.notifier).requestOtp(_phoneController.text.trim());
      setState(() => _otpSent = true);
    } catch (e) {
      setState(() => _error = apiErrorMessage(e));
    } finally {
      if (mounted) setState(() => _isSubmitting = false);
    }
  }

  Future<void> _verifyOtp() async {
    setState(() {
      _isSubmitting = true;
      _error = null;
    });
    try {
      await ref
          .read(authControllerProvider.notifier)
          .verifyOtp(phone: _phoneController.text.trim(), code: _codeController.text.trim());
      if (mounted) context.pop();
    } catch (e) {
      setState(() => _error = apiErrorMessage(e));
    } finally {
      if (mounted) setState(() => _isSubmitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);

    return Scaffold(
      appBar: AppBar(title: Text(l10n.login)),
      body: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            TextField(
              controller: _phoneController,
              enabled: !_otpSent,
              keyboardType: TextInputType.phone,
              decoration: InputDecoration(
                labelText: l10n.loginPhone,
                hintText: l10n.loginPhoneHint,
                border: const OutlineInputBorder(),
              ),
            ),
            if (_otpSent) ...[
              const SizedBox(height: 12),
              Text(l10n.loginOtpSent, style: Theme.of(context).textTheme.bodySmall),
              const SizedBox(height: 12),
              TextField(
                controller: _codeController,
                keyboardType: TextInputType.number,
                maxLength: 6,
                decoration: InputDecoration(
                  labelText: l10n.loginOtpCode,
                  border: const OutlineInputBorder(),
                ),
              ),
            ],
            if (_error != null) ...[
              const SizedBox(height: 8),
              Text(_error!, style: TextStyle(color: Theme.of(context).colorScheme.error)),
            ],
            const SizedBox(height: 16),
            FilledButton(
              onPressed: _isSubmitting ? null : (_otpSent ? _verifyOtp : _requestOtp),
              child: Text(_otpSent ? l10n.loginVerify : l10n.loginRequestCode),
            ),
            if (_otpSent)
              TextButton(
                onPressed: _isSubmitting
                    ? null
                    : () => setState(() {
                        _otpSent = false;
                        _codeController.clear();
                      }),
                child: Text(l10n.loginChangeNumber),
              ),
          ],
        ),
      ),
    );
  }
}
