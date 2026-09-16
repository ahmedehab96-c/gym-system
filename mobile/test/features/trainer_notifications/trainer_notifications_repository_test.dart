import 'package:flutter_test/flutter_test.dart';
import 'package:gym_member_app/core/network/api_client.dart';
import 'package:gym_member_app/core/network/cached_fetch.dart';
import 'package:gym_member_app/core/storage/cache_store.dart';
import 'package:gym_member_app/features/trainer_notifications/data/trainer_notifications_repository.dart';
import 'package:mocktail/mocktail.dart';

class MockApiClient extends Mock implements ApiClient {}

class MockCacheStore extends Mock implements CacheStore {}

void main() {
  late MockApiClient apiClient;
  late MockCacheStore cacheStore;
  late TrainerNotificationsRepository repository;

  setUp(() {
    apiClient = MockApiClient();
    cacheStore = MockCacheStore();
    when(() => cacheStore.write(any(), any(), any())).thenAnswer((_) async {});
    repository = TrainerNotificationsRepository(apiClient, CachedFetch(cacheStore, 'trainer'));
  });

  test('list() calls the existing staff /notifications endpoint (no new backend surface)', () async {
    when(() => apiClient.get('/notifications', query: any(named: 'query'))).thenAnswer((_) async => {
          'data': [
            {'id': 1, 'type': 'Class Reminder', 'title': 'Class soon', 'message': 'HIIT at 6pm', 'read': false, 'createdAt': '2026-09-12T10:00:00Z'},
          ],
          'meta': {'page': 1, 'perPage': 15, 'total': 1, 'totalPages': 1},
        });

    final result = await repository.list();

    result.when(
      success: (cached) => expect(cached.data.items, hasLength(1)),
      failure: (_) => fail('expected success'),
    );
  });

  test('markRead() PATCHes the notification with read=true', () async {
    when(() => apiClient.patch('/notifications/1', data: any(named: 'data'))).thenAnswer((_) async => {
          'data': {'id': 1, 'type': 'Class Reminder', 'title': 'Class soon', 'message': 'HIIT at 6pm', 'read': true, 'createdAt': '2026-09-12T10:00:00Z'},
        });

    final result = await repository.markRead(1);

    result.when(
      success: (notification) => expect(notification.read, isTrue),
      failure: (_) => fail('expected success'),
    );
    final captured = verify(() => apiClient.patch('/notifications/1', data: captureAny(named: 'data'))).captured.single as Map;
    expect(captured['read'], isTrue);
  });
}
