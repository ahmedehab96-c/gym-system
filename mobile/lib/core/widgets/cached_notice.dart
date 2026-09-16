import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

/// Dropped at the top of a screen's content whenever it's showing a
/// `Cached<T>` value with `meta.isFromCache == true` (Phase 27 §3:
/// "clearly indicate when data is cached/stale"). Deliberately quiet —
/// a single inline line, not a dialog or a colored banner — since the
/// global OfflineBannerOverlay already communicates connectivity state;
/// this just says how old *this* screen's data specifically is.
class CachedDataNotice extends StatelessWidget {
  const CachedDataNotice({required this.cachedAt, super.key});

  final DateTime? cachedAt;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    final label = cachedAt == null ? 'Showing saved data' : 'Showing saved data · updated ${_relative(cachedAt!)}';

    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(Icons.history_rounded, size: 14, color: scheme.onSurfaceVariant),
          const SizedBox(width: 6),
          Text(label, style: TextStyle(fontSize: 12, color: scheme.onSurfaceVariant, fontWeight: FontWeight.w500)),
        ],
      ),
    );
  }

  static String _relative(DateTime time) {
    final diff = DateTime.now().difference(time);
    if (diff.inMinutes < 1) return 'just now';
    if (diff.inMinutes < 60) return '${diff.inMinutes}m ago';
    if (diff.inHours < 24) return '${diff.inHours}h ago';
    if (diff.inDays < 7) return '${diff.inDays}d ago';
    return DateFormat.yMMMd().format(time);
  }
}
