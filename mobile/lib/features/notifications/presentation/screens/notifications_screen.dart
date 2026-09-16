import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/network/cached.dart';
import '../../../../core/sync/sync_manager.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/widgets/async_value_view.dart';
import '../../../../core/widgets/cached_notice.dart';
import '../../../../shared/models/app_notification.dart';
import '../../../../shared/models/paginated.dart';
import '../providers/notifications_provider.dart';

const _typeIcons = <String, IconData>{
  'Membership Expiring': Icons.warning_amber_rounded,
  'Membership Expired': Icons.warning_rounded,
  'Payment Received': Icons.check_circle_outline_rounded,
  'Payment Failed': Icons.error_outline_rounded,
  'New Member': Icons.celebration_outlined,
  'Class Reminder': Icons.event_outlined,
  'Class Cancellation': Icons.event_busy_outlined,
  'Invoice Due Reminder': Icons.receipt_long_outlined,
};

class NotificationsScreen extends ConsumerWidget {
  const NotificationsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final notifications = ref.watch(notificationsListProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Notifications'),
        actions: [
          TextButton(
            onPressed: () => ref.read(notificationActionsProvider).markAllRead(),
            child: const Text('Mark all read'),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () => ref.read(syncManagerProvider).syncNow(),
        child: AsyncValueView<Cached<Paginated<AppNotification>>>(
          value: notifications,
          onRetry: () => ref.invalidate(notificationsListProvider),
          isEmpty: (cached) => cached.data.items.isEmpty,
          emptyMessage: "You're all caught up.",
          emptyIcon: Icons.notifications_none_rounded,
          data: (cached) => Column(
            children: [
              if (cached.meta.isFromCache)
                Padding(padding: const EdgeInsets.fromLTRB(16, 12, 16, 0), child: CachedDataNotice(cachedAt: cached.meta.cachedAt)),
              Expanded(
                child: ListView.separated(
                  itemCount: cached.data.items.length,
                  separatorBuilder: (_, _) => const Divider(height: 1),
                  itemBuilder: (context, i) => _NotificationTile(notification: cached.data.items[i]),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _NotificationTile extends ConsumerWidget {
  const _NotificationTile({required this.notification});

  final AppNotification notification;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final scheme = Theme.of(context).colorScheme;

    return ListTile(
      onTap: notification.read ? null : () => ref.read(notificationActionsProvider).markRead(notification.id),
      tileColor: notification.read ? null : scheme.primaryContainer.withValues(alpha: 0.15),
      leading: CircleAvatar(
        backgroundColor: scheme.surfaceContainerHighest,
        child: Icon(_typeIcons[notification.type] ?? Icons.notifications_none_rounded, size: 18, color: scheme.primary),
      ),
      title: Text(notification.title, style: const TextStyle(fontWeight: FontWeight.w600)),
      subtitle: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(notification.message),
          const SizedBox(height: 3),
          Text(Formatters.timeAgo(notification.createdAt), style: TextStyle(fontSize: 11, color: scheme.onSurfaceVariant)),
        ],
      ),
      isThreeLine: true,
      trailing: notification.read ? null : Container(width: 9, height: 9, decoration: BoxDecoration(color: scheme.primary, shape: BoxShape.circle)),
    );
  }
}
