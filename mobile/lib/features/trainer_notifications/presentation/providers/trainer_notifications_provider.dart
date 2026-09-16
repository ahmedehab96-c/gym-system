import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:uuid/uuid.dart';

import '../../../../core/network/api_exception.dart';
import '../../../../core/network/api_result.dart';
import '../../../../core/network/cached.dart';
import '../../../../core/providers/core_providers.dart';
import '../../../../core/sync/offline_action.dart';
import '../../../../shared/models/app_notification.dart';
import '../../../../shared/models/paginated.dart';
import '../../data/trainer_notifications_repository.dart';

final trainerNotificationsRepositoryProvider =
    Provider((ref) => TrainerNotificationsRepository(ref.watch(apiClientProvider), ref.watch(trainerCachedFetchProvider)));

final trainerNotificationsListProvider = FutureProvider.autoDispose<Cached<Paginated<AppNotification>>>((ref) async {
  final result = await ref.watch(trainerNotificationsRepositoryProvider).list();
  return result.when(success: (data) => data, failure: (error) => throw error);
});

/// Mirrors the Member app's NotificationActions — see its docblock for
/// why marking read/all-read is safe to queue offline (Phase 27 §5).
class TrainerNotificationActions {
  TrainerNotificationActions(this._ref);

  final Ref _ref;
  static const _uuid = Uuid();

  Future<void> markRead(int id) async {
    final result = await _ref.read(trainerNotificationsRepositoryProvider).markRead(id);
    switch (result) {
      case ApiSuccess():
        _ref.invalidate(trainerNotificationsListProvider);
      case ApiFailure(:final error):
        if (error is NetworkException) {
          await _ref.read(trainerOfflineActionQueueProvider).enqueue(OfflineAction(
                id: _uuid.v4(),
                kind: OfflineActionKind.notificationMarkRead,
                payload: {'id': id},
                createdAt: DateTime.now(),
              ));
        }
    }
  }

  Future<void> markAllRead() async {
    final result = await _ref.read(trainerNotificationsRepositoryProvider).markAllRead();
    switch (result) {
      case ApiSuccess():
        _ref.invalidate(trainerNotificationsListProvider);
      case ApiFailure(:final error):
        if (error is NetworkException) {
          await _ref.read(trainerOfflineActionQueueProvider).enqueue(OfflineAction(
                id: _uuid.v4(),
                kind: OfflineActionKind.notificationMarkAllRead,
                payload: const {},
                createdAt: DateTime.now(),
              ));
        }
    }
  }
}

final trainerNotificationActionsProvider = Provider((ref) => TrainerNotificationActions(ref));
