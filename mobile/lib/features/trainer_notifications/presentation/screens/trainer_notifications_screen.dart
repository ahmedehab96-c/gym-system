import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/network/cached.dart';
import '../../../../core/sync/sync_manager.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/widgets/async_value_view.dart';
import '../../../../core/widgets/cached_notice.dart';
import '../../../../shared/models/app_notification.dart';
import '../../../../shared/models/paginated.dart';
import '../providers/trainer_notifications_provider.dart';

class TrainerNotificationsScreen extends ConsumerWidget {
  const TrainerNotificationsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final notifications = ref.watch(trainerNotificationsListProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Notifications'),
        actions: [
          TextButton(onPressed: () => ref.read(trainerNotificationActionsProvider).markAllRead(), child: const Text('Mark all read')),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () => ref.read(syncManagerProvider).syncNow(),
        child: AsyncValueView<Cached<Paginated<AppNotification>>>(
          value: notifications,
          onRetry: () => ref.invalidate(trainerNotificationsListProvider),
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
      onTap: () => _showDetails(context, ref),
      tileColor: notification.read ? null : scheme.primaryContainer.withValues(alpha: 0.15),
      leading: CircleAvatar(backgroundColor: scheme.surfaceContainerHighest, child: Icon(Icons.notifications_none_rounded, size: 18, color: scheme.primary)),
      title: Text(notification.title, style: const TextStyle(fontWeight: FontWeight.w600)),
      subtitle: Text(notification.message, maxLines: 2, overflow: TextOverflow.ellipsis),
      trailing: notification.read ? null : Container(width: 9, height: 9, decoration: BoxDecoration(color: scheme.primary, shape: BoxShape.circle)),
    );
  }

  void _showDetails(BuildContext context, WidgetRef ref) {
    if (!notification.read) {
      ref.read(trainerNotificationActionsProvider).markRead(notification.id);
    }

    showModalBottomSheet(
      context: context,
      showDragHandle: true,
      builder: (context) => Padding(
        padding: const EdgeInsets.fromLTRB(20, 0, 20, 32),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(notification.title, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w700)),
            const SizedBox(height: 8),
            Text(notification.message),
            const SizedBox(height: 12),
            Text(Formatters.dateTime(notification.createdAt), style: TextStyle(color: Theme.of(context).colorScheme.onSurfaceVariant, fontSize: 12)),
          ],
        ),
      ),
    );
  }
}
