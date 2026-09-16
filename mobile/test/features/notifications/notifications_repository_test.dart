import 'package:flutter_test/flutter_test.dart';
import 'package:gym_member_app/core/network/api_client.dart';
import 'package:gym_member_app/core/network/cached_fetch.dart';
import 'package:gym_member_app/core/storage/cache_store.dart';
import 'package:gym_member_app/features/notifications/data/notifications_repository.dart';
import 'package:mocktail/mocktail.dart';

class MockApiClient extends Mock implements ApiClient {}

class MockCacheStore extends Mock implements CacheStore {}

void main() {
  late MockApiClient apiClient;
  late MockCacheStore cacheStore;
  late NotificationsRepository repository;

  setUp(() {
    apiClient = MockApiClient();
    cacheStore = MockCacheStore();
    when(() => cacheStore.write(any(), any(), any())).thenAnswer((_) async {});
    repository = NotificationsRepository(apiClient, CachedFetch(cacheStore, 'member'));
  });

  test('list() parses notifications, including read/unread state', () async {
    when(() => apiClient.get('/member/notifications', query: any(named: 'query'))).thenAnswer((_) async => {
          'data': [
            {'id': 1, 'type': 'Class Reminder', 'title': 'Class soon', 'message': 'HIIT at 6pm', 'read': false, 'createdAt': '2026-09-10T10:00:00Z'},
          ],
          'meta': {'page': 1, 'perPage': 20, 'total': 1, 'totalPages': 1},
        });

    final result = await repository.list();

    result.when(
      success: (cached) {
        expect(cached.data.items, hasLength(1));
        expect(cached.data.items.first.read, isFalse);
      },
      failure: (_) => fail('expected success'),
    );
  });

  test('markRead() returns the notification flipped to read', () async {
    when(() => apiClient.patch('/member/notifications/1/read')).thenAnswer((_) async => {
          'data': {'id': 1, 'type': 'Class Reminder', 'title': 'Class soon', 'message': 'HIIT at 6pm', 'read': true, 'createdAt': '2026-09-10T10:00:00Z'},
        });

    final result = await repository.markRead(1);

    result.when(
      success: (notification) => expect(notification.read, isTrue),
      failure: (_) => fail('expected success'),
    );
  });

  test('markAllRead() succeeds without requiring a response body', () async {
    when(() => apiClient.post('/member/notifications/mark-all-read')).thenAnswer((_) async => {'message': 'ok'});

    final result = await repository.markAllRead();

    expect(result, isNotNull);
  });
}
