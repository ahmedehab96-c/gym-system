import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../features/attendance/presentation/providers/attendance_provider.dart';
import '../../features/classes/presentation/providers/classes_provider.dart';
import '../../features/membership/presentation/providers/membership_provider.dart';
import '../../features/notifications/presentation/providers/notifications_provider.dart';
import '../../features/programs/presentation/providers/programs_provider.dart';
import '../../features/trainer_classes/presentation/providers/trainer_classes_provider.dart';
import '../../features/trainer_members/presentation/providers/trainer_members_provider.dart';
import '../../features/trainer_notifications/presentation/providers/trainer_notifications_provider.dart';
import '../../features/trainer_profile/presentation/providers/trainer_profile_provider.dart';
import '../../features/trainer_programs/presentation/providers/trainer_programs_provider.dart';
import '../network/api_exception.dart';
import '../network/api_result.dart';
import '../providers/core_providers.dart';
import 'offline_action.dart';
import 'offline_action_queue.dart';

enum _FlushOutcome { succeeded, permanentlyFailed, stillOffline }

/// The single centralized sync coordinator (Phase 27 §4) — both apps
/// share one instance. Responsibilities:
///
/// 1. Flush each actor's queued offline mutations, retrying a
///    NetworkException with a short exponential backoff before giving up
///    on this pass (left queued for the next reconnect).
/// 2. Refresh every cache-backed provider after a successful flush, so
///    the server's answer always wins over anything a queued action or
///    a stale cache implied (Phase 27 §6 "server wins" / "refresh
///    affected local data after successful synchronization").
/// 3. Guard against overlapping runs — triggered both automatically on
///    reconnect (ConnectionController) and manually (pull-to-refresh).
class SyncManager {
  SyncManager(this._ref, {Duration Function(int attempt)? retryDelay}) : _retryDelay = retryDelay ?? _defaultRetryDelay;

  static Duration _defaultRetryDelay(int attempt) => Duration(seconds: 1 << attempt); // 1s, 2s

  final Ref _ref;
  final Duration Function(int attempt) _retryDelay;
  bool _isSyncing = false;

  Future<void> syncNow() async {
    if (_isSyncing) return;
    _isSyncing = true;
    try {
      await _flushQueue('member', _ref.read(memberOfflineActionQueueProvider));
      await _flushQueue('trainer', _ref.read(trainerOfflineActionQueueProvider));
      _refreshEverything();
    } finally {
      _isSyncing = false;
    }
  }

  Future<void> _flushQueue(String namespace, OfflineActionQueue queue) async {
    for (final action in await queue.all()) {
      switch (await _performWithRetry(namespace, action)) {
        case _FlushOutcome.succeeded:
        case _FlushOutcome.permanentlyFailed:
          await queue.remove(action.id);
        case _FlushOutcome.stillOffline:
          // Still no real connectivity — stop this pass; everything from
          // here stays queued in order for the next reconnect rather
          // than being retried out of order.
          return;
      }
    }
  }

  Future<_FlushOutcome> _performWithRetry(String namespace, OfflineAction action) async {
    const maxAttempts = 3;
    for (var attempt = 0; attempt < maxAttempts; attempt++) {
      final result = await _perform(namespace, action);
      switch (result) {
        case ApiSuccess():
          return _FlushOutcome.succeeded;
        case ApiFailure(:final error):
          if (error is! NetworkException) return _FlushOutcome.permanentlyFailed;
          if (attempt == maxAttempts - 1) return _FlushOutcome.stillOffline;
          await Future<void>.delayed(_retryDelay(attempt));
      }
    }
    return _FlushOutcome.stillOffline;
  }

  Future<ApiResult<dynamic>> _perform(String namespace, OfflineAction action) {
    if (namespace == 'member') {
      switch (action.kind) {
        case OfflineActionKind.classBook:
          return _ref.read(classesRepositoryProvider).book(action.payload['classId'] as int);
        case OfflineActionKind.classCancel:
          return _ref.read(classesRepositoryProvider).cancel(action.payload['classId'] as int);
        case OfflineActionKind.notificationMarkRead:
          return _ref.read(notificationsRepositoryProvider).markRead(action.payload['id'] as int);
        case OfflineActionKind.notificationMarkAllRead:
          return _ref.read(notificationsRepositoryProvider).markAllRead();
      }
    } else {
      switch (action.kind) {
        case OfflineActionKind.notificationMarkRead:
          return _ref.read(trainerNotificationsRepositoryProvider).markRead(action.payload['id'] as int);
        case OfflineActionKind.notificationMarkAllRead:
          return _ref.read(trainerNotificationsRepositoryProvider).markAllRead();
        case OfflineActionKind.classBook:
        case OfflineActionKind.classCancel:
          throw StateError('Trainer app never queues $action');
      }
    }
  }

  /// Cache invalidation after a successful sync pass — every provider
  /// backed by CachedFetch simply refetches (live if online, which it is
  /// here by definition) and overwrites its cache entry, so the server's
  /// current state always wins.
  void _refreshEverything() {
    _ref.invalidate(classesListProvider);
    _ref.invalidate(myBookingsProvider);
    _ref.invalidate(attendanceHistoryProvider);
    _ref.invalidate(attendanceSummaryProvider);
    _ref.invalidate(notificationsListProvider);
    _ref.invalidate(programsListProvider);
    _ref.invalidate(myProgramsProvider);
    _ref.invalidate(currentMembershipProvider);
    _ref.invalidate(membershipHistoryProvider);

    _ref.invalidate(currentTrainerProfileCachedProvider);
    _ref.invalidate(trainerMyClassesProvider);
    _ref.invalidate(trainerDailyScheduleProvider);
    _ref.invalidate(trainerWeeklyScheduleProvider);
    _ref.invalidate(trainerMonthlyScheduleProvider);
    _ref.invalidate(trainerAssignedMembersProvider);
    _ref.invalidate(trainerAssignedProgramsProvider);
    _ref.invalidate(trainerNotificationsListProvider);
  }
}

final syncManagerProvider = Provider<SyncManager>((ref) => SyncManager(ref));
