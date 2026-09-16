import 'package:flutter_test/flutter_test.dart';
import 'package:gym_member_app/core/network/api_client.dart';
import 'package:gym_member_app/core/network/api_exception.dart';
import 'package:gym_member_app/core/network/api_result.dart';
import 'package:gym_member_app/core/network/cached_fetch.dart';
import 'package:gym_member_app/core/storage/cache_store.dart';
import 'package:gym_member_app/features/classes/data/classes_repository.dart';
import 'package:mocktail/mocktail.dart';

class MockApiClient extends Mock implements ApiClient {}

class MockCacheStore extends Mock implements CacheStore {}

void main() {
  late MockApiClient apiClient;
  late MockCacheStore cacheStore;
  late ClassesRepository repository;

  setUp(() {
    apiClient = MockApiClient();
    cacheStore = MockCacheStore();
    when(() => cacheStore.write(any(), any(), any())).thenAnswer((_) async {});
    when(() => cacheStore.read(any(), any())).thenAnswer((_) async => null);
    repository = ClassesRepository(apiClient, CachedFetch(cacheStore, 'member'));
  });

  test('list() parses a paginated response of classes', () async {
    when(() => apiClient.get('/member/classes', query: any(named: 'query'))).thenAnswer((_) async => {
          'data': [
            {'id': 1, 'name': 'HIIT', 'capacity': 10, 'booked': 2, 'status': 'Scheduled'},
          ],
          'meta': {'page': 1, 'perPage': 20, 'total': 1, 'totalPages': 1},
        });

    final result = await repository.list();

    result.when(
      success: (cached) {
        expect(cached.data.items, hasLength(1));
        expect(cached.data.items.first.name, 'HIIT');
      },
      failure: (_) => fail('expected success'),
    );
  });

  test('list() forwards a non-empty search term as a query param and caches it under its own key', () async {
    when(() => apiClient.get('/member/classes', query: {'page': 1, 'search': 'yoga'})).thenAnswer((_) async => {
          'data': [
            {'id': 2, 'name': 'Sunrise Yoga', 'capacity': 12, 'booked': 3, 'status': 'Scheduled'},
          ],
          'meta': {'page': 1, 'perPage': 20, 'total': 1, 'totalPages': 1},
        });

    final result = await repository.list(search: 'yoga');

    result.when(
      success: (cached) => expect(cached.data.items.single.name, 'Sunrise Yoga'),
      failure: (_) => fail('expected success'),
    );
    verify(() => cacheStore.write('member', 'classes_search_yoga_p1', any())).called(1);
  });

  test('book() prevents a duplicate booking by surfacing the backend 422 as a failure', () async {
    when(() => apiClient.post('/member/classes/1/book'))
        .thenThrow(const ValidationException('You are already booked into this class.', {}));

    final result = await repository.book(1);

    result.when(
      success: (_) => fail('expected failure'),
      failure: (error) => expect(error.message, 'You are already booked into this class.'),
    );
  });

  test('cancel() maps a successful response back to the updated class', () async {
    when(() => apiClient.delete('/member/classes/1/book')).thenAnswer((_) async => {
          'data': {'id': 1, 'name': 'HIIT', 'capacity': 10, 'booked': 1, 'status': 'Scheduled'},
        });

    final result = await repository.cancel(1);

    result.when(
      success: (gymClass) => expect(gymClass.booked, 1),
      failure: (_) => fail('expected success'),
    );
  });

  test('propagates a network failure without throwing when nothing is cached', () async {
    when(() => apiClient.get('/member/classes/99')).thenThrow(const NetworkException());

    final result = await repository.show(99);

    expect(result, isA<ApiFailure<dynamic>>());
  });

  test('falls back to the last-cached page on a network failure', () async {
    when(() => apiClient.get('/member/classes', query: any(named: 'query'))).thenThrow(const NetworkException());
    when(() => cacheStore.read('member', 'classes_list_p1')).thenAnswer((_) async => CacheEntry(
          json: {
            'data': [
              {'id': 1, 'name': 'HIIT', 'capacity': 10, 'booked': 2, 'status': 'Scheduled'},
            ],
            'meta': {'page': 1, 'perPage': 20, 'total': 1, 'totalPages': 1},
          },
          cachedAt: DateTime(2026, 9, 1),
        ));

    final result = await repository.list();

    result.when(
      success: (cached) {
        expect(cached.meta.isFromCache, isTrue);
        expect(cached.meta.cachedAt, DateTime(2026, 9, 1));
        expect(cached.data.items.single.name, 'HIIT');
      },
      failure: (_) => fail('expected a cache-fallback success'),
    );
  });
}
