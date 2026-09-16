import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../providers/connectivity_provider.dart';

/// Wraps the whole app (see app.dart) with a slim, non-intrusive strip
/// that only appears when there's something to say — offline, or briefly
/// reconnecting — and disappears entirely once back online, rather than
/// permanently reserving space in the premium UI (Phase 27 §2: "a subtle
/// professional offline indicator without damaging the premium UI").
class OfflineBannerOverlay extends ConsumerWidget {
  const OfflineBannerOverlay({required this.child, super.key});

  final Widget child;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final status = ref.watch(connectionStatusProvider);
    final scheme = Theme.of(context).colorScheme;

    final (label, icon, color) = switch (status) {
      ConnectionStatus.online => (null, null, null),
      ConnectionStatus.offline => ('Offline — showing saved data', Icons.cloud_off_rounded, scheme.error),
      ConnectionStatus.reconnecting => ('Reconnecting…', Icons.sync_rounded, scheme.tertiary),
    };

    return Column(
      children: [
        ClipRect(
          child: AnimatedSize(
            duration: const Duration(milliseconds: 220),
            curve: Curves.easeOut,
            alignment: Alignment.topCenter,
            child: label == null
                ? const SizedBox(width: double.infinity)
                : SafeArea(
                    bottom: false,
                    child: Container(
                      width: double.infinity,
                      color: color!.withValues(alpha: 0.12),
                      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Icon(icon, size: 15, color: color),
                          const SizedBox(width: 8),
                          Text(label, style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.w600, color: color)),
                        ],
                      ),
                    ),
                  ),
          ),
        ),
        Expanded(child: child),
      ],
    );
  }
}
