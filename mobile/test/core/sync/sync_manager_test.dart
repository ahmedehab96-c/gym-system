import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:gym_member_app/core/network/api_client.dart';
import 'package:gym_member_app/core/network/api_exception.dart';
import 'package:gym_member_app/core/providers/core_providers.dart';
import 'package:gym_member_app/core/storage/secure_storage.dart';
import 'package:gym_member_app/core/sync/offline_action.dart';
import 'package:gym_member_app/core/sync/sync_manager.dart';
import 'package:mocktail/mocktail.dart';
import 'package:shared_preferences_platform_interface/in_memory_shared_preferences_async.dart';
import 'package:shared_preferences_platform_interface/shared_preferences_async_platform_interface.dart';

class MockApiClient extends Mock implements ApiClient {}

class MockSecureStorage extends Mock implements SecureStorage {}

void main() {
  late MockApiClient apiClient;
  late ProviderContainer container;

  setUp(() {
    SharedPreferencesAsyncPlatform.instance = InMemorySharedPreferencesAsync.empty();
    apiClient = MockApiClient();
    // _refreshEverything() invalidates currentTrainerProfileCachedProvider,
    // which (re)builds trainerAuthControllerProvider — give it a no-op
    // stored session so that settles into Unauthenticated without ever
    // touching the real SecureStorage plugin (unavailable in a unit test).
    final secureStorage = MockSecureStorage();
    when(() => secureStorage.readActorType()).thenAnswer((_) async => null);
    when(() => secureStorage.readToken()).thenAnswer((_) async => null);
    container = ProviderContainer(overrides: [
      apiClientProvider.overrideWithValue(apiClient),
      secureStorageProvider.overrideWithValue(secureStorage),
      syncManagerProvider.overrideWith((ref) => SyncManager(ref, retryDelay: (_) => Duration.zero)),
    ]);
  });

  tearDown(() => container.dispose());

  SyncManager manager() => container.read(syncManagerProvider);

  test('flushes a queued member classCancel action and removes it from the queue on success', () async {
    await container.read(memberOfflineActionQueueProvider).enqueue(OfflineAction(
          id: 'a1',
          kind: OfflineActionKind.classCancel,
          payload: {'classId': 1},
          createdAt: DateTime(2026, 9, 1),
        ));
    when(() => apiClient.delete('/member/classes/1/book')).thenAnswer((_) async => {
          'data': {'id': 1, 'name': 'HIIT', 'capacity': 10, 'booked': 0, 'status': 'Scheduled'},
        });

    await manager().syncNow();

    expect(await container.read(memberOfflineActionQueueProvider).all(), isEmpty);
    verify(() => apiClient.delete('/member/classes/1/book')).called(1);
  });

  test('a non-network failure (e.g. validation) drops the action instead of retrying forever', () async {
    await container.read(memberOfflineActionQueueProvider).enqueue(OfflineAction(
          id: 'a1',
          kind: OfflineActionKind.classBook,
          payload: {'classId': 1},
          createdAt: DateTime(2026, 9, 1),
        ));
    when(() => apiClient.post('/member/classes/1/book')).thenThrow(const ValidationException('Class is full.', {}));

    await manager().syncNow();

    expect(await container.read(memberOfflineActionQueueProvider).all(), isEmpty);
  });

  test('a NetworkException leaves the action queued for the next reconnect attempt', () async {
    await container.read(memberOfflineActionQueueProvider).enqueue(OfflineAction(
          id: 'a1',
          kind: OfflineActionKind.notificationMarkRead,
          payload: {'id': 9},
          createdAt: DateTime(2026, 9, 1),
        ));
    when(() => apiClient.patch('/member/notifications/9/read')).thenThrow(const NetworkException());

    await manager().syncNow();

    final remaining = await container.read(memberOfflineActionQueueProvider).all();
    expect(remaining, hasLength(1));
    expect(remaining.single.id, 'a1');
  });

  test('trainer queue actions are dispatched to the trainer notifications repository', () async {
    await container.read(trainerOfflineActionQueueProvider).enqueue(OfflineAction(
          id: 't1',
          kind: OfflineActionKind.notificationMarkAllRead,
          payload: const {},
          createdAt: DateTime(2026, 9, 1),
        ));
    when(() => apiClient.post('/notifications/mark-all-read')).thenAnswer((_) async => {'message': 'ok'});

    await manager().syncNow();

    expect(await container.read(trainerOfflineActionQueueProvider).all(), isEmpty);
    verify(() => apiClient.post('/notifications/mark-all-read')).called(1);
  });

  test('a second concurrent syncNow() call is a no-op while one is already running', () async {
    await container.read(memberOfflineActionQueueProvider).enqueue(OfflineAction(
          id: 'a1',
          kind: OfflineActionKind.classCancel,
          payload: {'classId': 1},
          createdAt: DateTime(2026, 9, 1),
        ));
    var callCount = 0;
    when(() => apiClient.delete('/member/classes/1/book')).thenAnswer((_) async {
      callCount++;
      await Future<void>.delayed(const Duration(milliseconds: 50));
      return {
        'data': {'id': 1, 'name': 'HIIT', 'capacity': 10, 'booked': 0, 'status': 'Scheduled'},
      };
    });

    final syncManager = manager();
    await Future.wait([syncManager.syncNow(), syncManager.syncNow()]);
    // Lets the provider-invalidation fallout from _refreshEverything()
    // (an autoDispose provider being created then torn down again with
    // no listener) finish settling before tearDown disposes the
    // container out from under it.
    await Future<void>.delayed(Duration.zero);

    expect(callCount, 1);
  });
}
