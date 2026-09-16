import 'package:flutter_test/flutter_test.dart';
import 'package:gym_member_app/core/network/api_exception.dart';
import 'package:gym_member_app/core/network/api_result.dart';
import 'package:gym_member_app/core/network/cached_fetch.dart';
import 'package:gym_member_app/core/storage/cache_store.dart';
import 'package:mocktail/mocktail.dart';

class MockCacheStore extends Mock implements CacheStore {}

void main() {
  late MockCacheStore cacheStore;
  late CachedFetch cachedFetch;

  setUp(() {
    cacheStore = MockCacheStore();
    cachedFetch = CachedFetch(cacheStore, 'member');
  });

  test('a successful fetch is returned live and written to the cache', () async {
    when(() => cacheStore.write('member', 'k', {'v': 1})).thenAnswer((_) async {});

    final result = await cachedFetch.call<int>(
      cacheKey: 'k',
      fetchRaw: () async => {'v': 1},
      parse: (raw) => raw['v'] as int,
    );

    result.when(
      success: (cached) {
        expect(cached.data, 1);
        expect(cached.meta.isFromCache, isFalse);
        expect(cached.meta.cachedAt, isNull);
      },
      failure: (_) => fail('expected success'),
    );
    verify(() => cacheStore.write('member', 'k', {'v': 1})).called(1);
  });

  test('a NetworkException falls back to a cached entry, marked as cached', () async {
    final cachedAt = DateTime(2026, 9, 1);
    when(() => cacheStore.read('member', 'k')).thenAnswer((_) async => CacheEntry(json: {'v': 2}, cachedAt: cachedAt));

    final result = await cachedFetch.call<int>(
      cacheKey: 'k',
      fetchRaw: () => throw const NetworkException(),
      parse: (raw) => raw['v'] as int,
    );

    result.when(
      success: (cached) {
        expect(cached.data, 2);
        expect(cached.meta.isFromCache, isTrue);
        expect(cached.meta.cachedAt, cachedAt);
      },
      failure: (_) => fail('expected a cache-fallback success'),
    );
  });

  test('a NetworkException with nothing cached surfaces as a failure', () async {
    when(() => cacheStore.read('member', 'k')).thenAnswer((_) async => null);

    final result = await cachedFetch.call<int>(
      cacheKey: 'k',
      fetchRaw: () => throw const NetworkException(),
      parse: (raw) => raw['v'] as int,
    );

    expect(result, isA<ApiFailure<dynamic>>());
  });

  test('a non-network failure (e.g. 403) is never masked by a cache fallback', () async {
    final result = await cachedFetch.call<int>(
      cacheKey: 'k',
      fetchRaw: () => throw const ForbiddenException(),
      parse: (raw) => raw['v'] as int,
    );

    result.when(
      success: (_) => fail('expected failure'),
      failure: (error) => expect(error, isA<ForbiddenException>()),
    );
    verifyNever(() => cacheStore.read(any(), any()));
  });
}
