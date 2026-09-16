import 'package:flutter_test/flutter_test.dart';
import 'package:gym_member_app/core/network/api_client.dart';
import 'package:gym_member_app/core/network/api_exception.dart';
import 'package:gym_member_app/core/network/api_result.dart';
import 'package:gym_member_app/core/network/cached_fetch.dart';
import 'package:gym_member_app/core/storage/cache_store.dart';
import 'package:gym_member_app/core/storage/secure_storage.dart';
import 'package:gym_member_app/features/trainer_auth/data/trainer_auth_repository.dart';
import 'package:mocktail/mocktail.dart';

class MockApiClient extends Mock implements ApiClient {}

class MockSecureStorage extends Mock implements SecureStorage {}

class MockCacheStore extends Mock implements CacheStore {}

void main() {
  late MockApiClient apiClient;
  late MockSecureStorage secureStorage;
  late MockCacheStore cacheStore;
  late TrainerAuthRepository repository;

  setUp(() {
    apiClient = MockApiClient();
    secureStorage = MockSecureStorage();
    cacheStore = MockCacheStore();
    when(() => cacheStore.clearNamespace(any())).thenAnswer((_) async {});
    when(() => cacheStore.write(any(), any(), any())).thenAnswer((_) async {});
    repository = TrainerAuthRepository(
      apiClient: apiClient,
      secureStorage: secureStorage,
      cacheStore: cacheStore,
      cachedFetch: CachedFetch(cacheStore, 'trainer'),
    );
  });

  test('login calls the SAME staff /auth/login endpoint the React admin uses, tagged as a trainer session', () async {
    when(() => apiClient.post('/auth/login', data: any(named: 'data'))).thenAnswer((_) async => {
          'data': {
            'token': 'staff-token-1',
            'user': {
              'id': 9, 'name': 'Coach Karim', 'email': 'karim@example.com', 'role': 'Trainer', 'status': 'Active', 'permissions': [],
            },
          },
        });
    when(() => secureStorage.saveToken(any())).thenAnswer((_) async {});
    when(() => secureStorage.saveActorType(any())).thenAnswer((_) async {});

    final result = await repository.login(email: 'karim@example.com', password: 'secret');

    expect(result, isA<ApiSuccess<dynamic>>());
    verify(() => secureStorage.saveToken('staff-token-1')).called(1);
    verify(() => secureStorage.saveActorType('trainer')).called(1);
  });

  test('a non-Trainer-role login still succeeds at the auth layer (role gating is a backend route concern, not login)', () async {
    when(() => apiClient.post('/auth/login', data: any(named: 'data'))).thenAnswer((_) async => {
          'data': {
            'token': 'staff-token-2',
            'user': {'id': 2, 'name': 'Admin User', 'email': 'admin@example.com', 'role': 'Admin', 'status': 'Active', 'permissions': []},
          },
        });
    when(() => secureStorage.saveToken(any())).thenAnswer((_) async {});
    when(() => secureStorage.saveActorType(any())).thenAnswer((_) async {});

    final result = await repository.login(email: 'admin@example.com', password: 'secret');

    result.when(
      success: (user) => expect(user.role, 'Admin'),
      failure: (_) => fail('expected success'),
    );
  });

  test('login failure never writes a token', () async {
    when(() => apiClient.post('/auth/login', data: any(named: 'data')))
        .thenThrow(const ValidationException('Invalid credentials', {}));

    final result = await repository.login(email: 'x@example.com', password: 'wrong');

    expect(result, isA<ApiFailure<dynamic>>());
    verifyNever(() => secureStorage.saveToken(any()));
  });

  test('hasStoredTrainerSession is false when the stored session belongs to a Member, not a Trainer', () async {
    when(() => secureStorage.readActorType()).thenAnswer((_) async => 'member');
    when(() => secureStorage.readToken()).thenAnswer((_) async => 'some-token');

    expect(await repository.hasStoredTrainerSession(), isFalse);
  });

  test('hasStoredTrainerSession is true only when both the token and actor type match', () async {
    when(() => secureStorage.readActorType()).thenAnswer((_) async => 'trainer');
    when(() => secureStorage.readToken()).thenAnswer((_) async => 'some-token');

    expect(await repository.hasStoredTrainerSession(), isTrue);
  });

  test('logout clears both the token and the actor type even if the server call fails', () async {
    when(() => apiClient.post('/auth/logout')).thenThrow(const NetworkException());
    when(() => secureStorage.clearToken()).thenAnswer((_) async {});
    when(() => secureStorage.clearActorType()).thenAnswer((_) async {});

    await repository.logout();

    verify(() => secureStorage.clearToken()).called(1);
    verify(() => secureStorage.clearActorType()).called(1);
  });
}
