import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:qr_flutter/qr_flutter.dart';

import '../../../../core/widgets/async_value_view.dart';
import '../../data/member_qr.dart';
import '../providers/qr_provider.dart';

/// Fullscreen "My QR" presentation (Phase 28 §1) — a member holds this up
/// at the front desk to scan. The token itself never changes based on
/// membership status; instead an inactive membership is shown as a clear
/// warning banner over an otherwise still-visible code, since the
/// backend (not this screen) is what actually accepts or declines the
/// scan (Phase 28 §7 "backend must remain the source of truth").
class MyQrScreen extends ConsumerWidget {
  const MyQrScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final qr = ref.watch(myQrProvider);
    final regenerateState = ref.watch(qrRegenerateControllerProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text('My QR Code'),
        actions: [
          IconButton(
            tooltip: 'Regenerate code',
            onPressed: regenerateState.isLoading ? null : () => _confirmRegenerate(context, ref),
            icon: const Icon(Icons.refresh_rounded),
          ),
        ],
      ),
      body: AsyncValueView<MemberQr>(
        value: qr,
        onRetry: () => ref.invalidate(myQrProvider),
        data: (memberQr) => _QrContent(memberQr: memberQr),
      ),
    );
  }

  Future<void> _confirmRegenerate(BuildContext context, WidgetRef ref) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Generate a new code?'),
        content: const Text('Your current QR code will stop working immediately. Use this if you think someone else has seen it.'),
        actions: [
          TextButton(onPressed: () => Navigator.of(context).pop(false), child: const Text('Cancel')),
          TextButton(onPressed: () => Navigator.of(context).pop(true), child: const Text('Regenerate')),
        ],
      ),
    );

    if (confirmed != true || !context.mounted) return;

    final error = await ref.read(qrRegenerateControllerProvider.notifier).regenerate();
    if (!context.mounted) return;
    if (error != null) {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(error)));
    } else {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('New QR code generated.')));
    }
  }
}

class _QrContent extends StatelessWidget {
  const _QrContent({required this.memberQr});

  final MemberQr memberQr;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    final usable = memberQr.membershipUsable && !memberQr.isExpired;

    return Center(
      child: SingleChildScrollView(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(28),
                boxShadow: [BoxShadow(color: scheme.shadow.withValues(alpha: 0.15), blurRadius: 24, offset: const Offset(0, 8))],
              ),
              child: Opacity(
                opacity: usable ? 1 : 0.35,
                child: QrImageView(
                  data: memberQr.token,
                  version: QrVersions.auto,
                  size: 240,
                  gapless: true,
                  padding: EdgeInsets.zero,
                ),
              ),
            ),
            const SizedBox(height: 24),
            if (!usable) _StatusWarning(memberQr: memberQr) else _StatusOk(),
            const SizedBox(height: 8),
            Text(
              'Show this to the front desk to check in instantly.',
              textAlign: TextAlign.center,
              style: TextStyle(color: scheme.onSurfaceVariant, fontSize: 13),
            ),
          ],
        ),
      ),
    );
  }
}

class _StatusOk extends StatelessWidget {
  const _StatusOk();

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Icon(Icons.check_circle_rounded, color: Colors.green.shade600, size: 18),
        const SizedBox(width: 6),
        Text('Membership active', style: TextStyle(color: Colors.green.shade700, fontWeight: FontWeight.w600)),
      ],
    );
  }
}

class _StatusWarning extends StatelessWidget {
  const _StatusWarning({required this.memberQr});

  final MemberQr memberQr;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    final message = memberQr.isExpired
        ? 'This QR code has expired. Tap refresh to generate a new one.'
        : 'Your membership is ${memberQr.memberStatus} — check-in may be declined at the front desk.';

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
      decoration: BoxDecoration(color: scheme.errorContainer, borderRadius: BorderRadius.circular(14)),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(Icons.error_outline_rounded, color: scheme.onErrorContainer, size: 18),
          const SizedBox(width: 8),
          Flexible(child: Text(message, style: TextStyle(color: scheme.onErrorContainer, fontWeight: FontWeight.w600, fontSize: 13))),
        ],
      ),
    );
  }
}
