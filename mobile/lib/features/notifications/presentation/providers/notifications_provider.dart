import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:uuid/uuid.dart';

import '../../../../core/network/api_exception.dart';
import '../../../../core/network/api_result.dart';
import '../../../../core/network/cached.dart';
import '../../../../core/providers/core_providers.dart';
import '../../../../core/sync/offline_action.dart';
import '../../../../shared/models/app_notification.dart';
import '../../../../shared/models/paginated.dart';
import '../../data/notifications_repository.dart';

final notificationsRepositoryProvider =
    Provider((ref) => NotificationsRepository(ref.watch(apiClientProvider), ref.watch(memberCachedFetchProvider)));

final notificationsListProvider = FutureProvider.autoDispose<Cached<Paginated<AppNotification>>>((ref) async {
  final result = await ref.watch(notificationsRepositoryProvider).list();
  return result.when(success: (data) => data, failure: (error) => throw error);
});

/// Phase 27 §5: marking read/all-read is idempotent (re-marking an
/// already-read notification is a harmless no-op server-side), so a
/// NetworkException here is queued instead of just being swallowed —
/// otherwise the member's tap would silently do nothing until they
/// happen to reopen the screen with connectivity back.
class NotificationActions {
  NotificationActions(this._ref);

  final Ref _ref;
  static const _uuid = Uuid();

  Future<void> markRead(int id) async {
    final result = await _ref.read(notificationsRepositoryProvider).markRead(id);
    switch (result) {
      case ApiSuccess():
        _ref.invalidate(notificationsListProvider);
      case ApiFailure(:final error):
        if (error is NetworkException) {
          await _ref.read(memberOfflineActionQueueProvider).enqueue(OfflineAction(
                id: _uuid.v4(),
                kind: OfflineActionKind.notificationMarkRead,
                payload: {'id': id},
                createdAt: DateTime.now(),
              ));
        }
    }
  }

  Future<void> markAllRead() async {
    final result = await _ref.read(notificationsRepositoryProvider).markAllRead();
    switch (result) {
      case ApiSuccess():
        _ref.invalidate(notificationsListProvider);
      case ApiFailure(:final error):
        if (error is NetworkException) {
          await _ref.read(memberOfflineActionQueueProvider).enqueue(OfflineAction(
                id: _uuid.v4(),
                kind: OfflineActionKind.notificationMarkAllRead,
                payload: const {},
                createdAt: DateTime.now(),
              ));
        }
    }
  }
}

final notificationActionsProvider = Provider((ref) => NotificationActions(ref));
