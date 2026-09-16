import 'package:flutter_test/flutter_test.dart';
import 'package:gym_member_app/core/network/api_client.dart';
import 'package:gym_member_app/core/network/api_exception.dart';
import 'package:gym_member_app/core/network/api_result.dart';
import 'package:gym_member_app/core/network/cached_fetch.dart';
import 'package:gym_member_app/core/storage/cache_store.dart';
import 'package:gym_member_app/core/storage/secure_storage.dart';
import 'package:gym_member_app/features/auth/data/auth_repository.dart';
import 'package:mocktail/mocktail.dart';

class MockApiClient extends Mock implements ApiClient {}

class MockSecureStorage extends Mock implements SecureStorage {}

class MockCacheStore extends Mock implements CacheStore {}

void main() {
  late MockApiClient apiClient;
  late MockSecureStorage secureStorage;
  late MockCacheStore cacheStore;
  late AuthRepository repository;

  setUp(() {
    apiClient = MockApiClient();
    secureStorage = MockSecureStorage();
    cacheStore = MockCacheStore();
    when(() => cacheStore.clearNamespace(any())).thenAnswer((_) async {});
    when(() => cacheStore.write(any(), any(), any())).thenAnswer((_) async {});
    repository = AuthRepository(
      apiClient: apiClient,
      secureStorage: secureStorage,
      cacheStore: cacheStore,
      cachedFetch: CachedFetch(cacheStore, 'member'),
    );
  });

  group('login', () {
    test('saves the token and returns the member on success', () async {
      when(() => apiClient.post('/member/auth/login', data: any(named: 'data'))).thenAnswer((_) async => {
            'data': {
              'token': 'sanctum-token-123',
              'member': {'id': 1, 'name': 'Alex', 'email': 'alex@example.com', 'status': 'Active'},
            },
          });
      when(() => secureStorage.saveToken(any())).thenAnswer((_) async {});
      when(() => secureStorage.saveActorType(any())).thenAnswer((_) async {});

      final result = await repository.login(email: 'alex@example.com', password: 'secret');

      expect(result, isA<ApiSuccess<dynamic>>());
      verify(() => secureStorage.saveToken('sanctum-token-123')).called(1);
      verify(() => secureStorage.saveActorType('member')).called(1);
    });

    test('clears any previous session\'s cache before writing the new token (Phase 27)', () async {
      when(() => apiClient.post('/member/auth/login', data: any(named: 'data'))).thenAnswer((_) async => {
            'data': {
              'token': 'sanctum-token-123',
              'member': {'id': 1, 'name': 'Alex', 'email': 'alex@example.com', 'status': 'Active'},
            },
          });
      when(() => secureStorage.saveToken(any())).thenAnswer((_) async {});
      when(() => secureStorage.saveActorType(any())).thenAnswer((_) async {});

      await repository.login(email: 'alex@example.com', password: 'secret');

      verify(() => cacheStore.clearNamespace('member')).called(1);
    });

    test('never writes a token when the API call fails (wrong credentials)', () async {
      when(() => apiClient.post('/member/auth/login', data: any(named: 'data')))
          .thenThrow(const ValidationException('These credentials do not match our records.', {'email': ['These credentials do not match our records.']}));

      final result = await repository.login(email: 'alex@example.com', password: 'wrong');

      expect(result, isA<ApiFailure<dynamic>>());
      verifyNever(() => secureStorage.saveToken(any()));
    });
  });

  group('logout', () {
    test('clears the local token and the cache even when the server call fails', () async {
      when(() => apiClient.post('/member/auth/logout')).thenThrow(const NetworkException());
      when(() => secureStorage.clearToken()).thenAnswer((_) async {});
      when(() => secureStorage.clearActorType()).thenAnswer((_) async {});

      await repository.logout();

      verify(() => secureStorage.clearToken()).called(1);
      verify(() => secureStorage.clearActorType()).called(1);
      verify(() => cacheStore.clearNamespace('member')).called(1);
    });
  });

  group('hasStoredSession', () {
    test('is true only when a token is present', () async {
      when(() => secureStorage.readToken()).thenAnswer((_) async => 'a-token');
      expect(await repository.hasStoredSession(), isTrue);

      when(() => secureStorage.readToken()).thenAnswer((_) async => null);
      expect(await repository.hasStoredSession(), isFalse);
    });
  });
}
