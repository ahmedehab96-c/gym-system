import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:uuid/uuid.dart';

import '../../../../core/network/api_exception.dart';
import '../../../../core/network/api_result.dart';
import '../../../../core/network/cached.dart';
import '../../../../core/providers/core_providers.dart';
import '../../../../core/sync/offline_action.dart';
import '../../../../shared/models/gym_class.dart';
import '../../../../shared/models/paginated.dart';
import '../../data/classes_repository.dart';

final classesRepositoryProvider =
    Provider((ref) => ClassesRepository(ref.watch(apiClientProvider), ref.watch(memberCachedFetchProvider)));

final classesListProvider = FutureProvider.autoDispose<Cached<Paginated<GymClass>>>((ref) async {
  final result = await ref.watch(classesRepositoryProvider).list();
  return result.when(success: (data) => data, failure: (error) => throw error);
});

/// Phase 29 §6 — a separate keyed provider so the plain, unfiltered
/// `classesListProvider` (and everything that already invalidates it,
/// e.g. SyncManager) is untouched; the screen just watches whichever one
/// applies to the current search box state.
final classesSearchProvider = FutureProvider.autoDispose.family<Cached<Paginated<GymClass>>, String>((ref, query) async {
  final result = await ref.watch(classesRepositoryProvider).list(search: query);
  return result.when(success: (data) => data, failure: (error) => throw error);
});

final myBookingsProvider = FutureProvider.autoDispose<Cached<Paginated<GymClass>>>((ref) async {
  final result = await ref.watch(classesRepositoryProvider).myBookings();
  return result.when(success: (data) => data, failure: (error) => throw error);
});

final classDetailProvider = FutureProvider.autoDispose.family<Cached<GymClass>, int>((ref, id) async {
  final result = await ref.watch(classesRepositoryProvider).show(id);
  return result.when(success: (data) => data, failure: (error) => throw error);
});

/// Tracks in-flight book/cancel so the detail screen can disable its
/// button and show a spinner without a full-screen reload.
///
/// Phase 27 §5: when a book/cancel fails specifically because there's no
/// connection, it's queued instead of just failing outright — the
/// backend's own duplicate-booking/duplicate-cancel guards (see
/// OfflineActionKind's docblock) make replaying it on reconnect safe, so
/// the member sees an optimistic "you're booked" rather than being told
/// to try again once they're back online.
class ClassActionController extends StateNotifier<AsyncValue<void>> {
  ClassActionController(this._ref) : super(const AsyncData(null));

  final Ref _ref;
  static const _uuid = Uuid();

  Future<String?> book(int classId) => _run(
        classId: classId,
        kind: OfflineActionKind.classBook,
        call: () => _ref.read(classesRepositoryProvider).book(classId),
      );

  Future<String?> cancel(int classId) => _run(
        classId: classId,
        kind: OfflineActionKind.classCancel,
        call: () => _ref.read(classesRepositoryProvider).cancel(classId),
      );

  // Uses a switch rather than ApiResult.when() — the failure branch needs
  // to await enqueueing the offline action, and .when() doesn't await
  // its callbacks (see AuthController.restoreSession's docblock for the
  // same reasoning).
  Future<String?> _run({
    required int classId,
    required OfflineActionKind kind,
    required Future<ApiResult<dynamic>> Function() call,
  }) async {
    state = const AsyncLoading();
    final result = await call();
    switch (result) {
      case ApiSuccess():
        state = const AsyncData(null);
        _invalidateAll(classId);
        return null;
      case ApiFailure(:final error):
        if (error is NetworkException) {
          await _ref.read(memberOfflineActionQueueProvider).enqueue(OfflineAction(
                id: _uuid.v4(),
                kind: kind,
                payload: {'classId': classId},
                createdAt: DateTime.now(),
              ));
          state = const AsyncData(null);
          _invalidateAll(classId);
          return null;
        }
        state = const AsyncData(null);
        return error.message;
    }
  }

  void _invalidateAll(int classId) {
    _ref.invalidate(classDetailProvider(classId));
    _ref.invalidate(myBookingsProvider);
    _ref.invalidate(classesListProvider);
  }
}

final classActionControllerProvider =
    StateNotifierProvider.autoDispose<ClassActionController, AsyncValue<void>>((ref) => ClassActionController(ref));
