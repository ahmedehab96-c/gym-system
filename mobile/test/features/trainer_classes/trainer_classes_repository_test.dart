import 'package:flutter_test/flutter_test.dart';
import 'package:gym_member_app/core/network/api_client.dart';
import 'package:gym_member_app/core/network/api_exception.dart';
import 'package:gym_member_app/core/network/api_result.dart';
import 'package:gym_member_app/core/network/cached_fetch.dart';
import 'package:gym_member_app/core/storage/cache_store.dart';
import 'package:gym_member_app/features/trainer_classes/data/trainer_classes_repository.dart';
import 'package:mocktail/mocktail.dart';

class MockApiClient extends Mock implements ApiClient {}

class MockCacheStore extends Mock implements CacheStore {}

void main() {
  late MockApiClient apiClient;
  late MockCacheStore cacheStore;
  late TrainerClassesRepository repository;

  setUp(() {
    apiClient = MockApiClient();
    cacheStore = MockCacheStore();
    when(() => cacheStore.write(any(), any(), any())).thenAnswer((_) async {});
    repository = TrainerClassesRepository(apiClient, CachedFetch(cacheStore, 'trainer'));
  });

  test('myClasses() calls the existing /classes endpoint filtered to this trainer', () async {
    when(() => apiClient.get('/classes', query: any(named: 'query'))).thenAnswer((_) async => {
          'data': [
            {'id': 1, 'name': 'HIIT', 'capacity': 10, 'booked': 3, 'status': 'Scheduled'},
          ],
          'meta': {'page': 1, 'perPage': 15, 'total': 1, 'totalPages': 1},
        });

    final result = await repository.myClasses(7);

    result.when(
      success: (cached) => expect(cached.data.items, hasLength(1)),
      failure: (_) => fail('expected success'),
    );
    final captured = verify(() => apiClient.get('/classes', query: captureAny(named: 'query'))).captured.single as Map;
    expect(captured['trainer_id'], 7);
  });

  test('myClasses() forwards a search term to the existing /classes endpoint', () async {
    when(() => apiClient.get('/classes', query: any(named: 'query'))).thenAnswer((_) async => {
          'data': [
            {'id': 2, 'name': 'Strength Basics', 'capacity': 8, 'booked': 1, 'status': 'Scheduled'},
          ],
          'meta': {'page': 1, 'perPage': 15, 'total': 1, 'totalPages': 1},
        });

    final result = await repository.myClasses(7, search: 'strength');

    result.when(
      success: (cached) => expect(cached.data.items.single.name, 'Strength Basics'),
      failure: (_) => fail('expected success'),
    );
    final captured = verify(() => apiClient.get('/classes', query: captureAny(named: 'query'))).captured.single as Map;
    expect(captured['search'], 'strength');
  });

  test('show() parses a class with its booked members list', () async {
    when(() => apiClient.get('/classes/1')).thenAnswer((_) async => {
          'data': {
            'id': 1, 'name': 'HIIT', 'capacity': 10, 'booked': 1, 'status': 'Scheduled',
            'bookedMembers': [
              {'id': 5, 'name': 'Alex Doe', 'bookedAt': '2026-09-10T10:00:00Z'},
            ],
          },
        });

    final result = await repository.show(1);

    result.when(
      success: (cached) {
        expect(cached.data.bookedMembers, hasLength(1));
        expect(cached.data.bookedMembers!.first.name, 'Alex Doe');
      },
      failure: (_) => fail('expected success'),
    );
  });

  test('daily() surfaces a server failure without throwing when nothing is cached', () async {
    when(() => apiClient.get('/schedule/daily', query: any(named: 'query'))).thenThrow(const ServerException());
    when(() => cacheStore.read(any(), any())).thenAnswer((_) async => null);

    final result = await repository.daily(7);

    expect(result, isA<ApiFailure<dynamic>>());
  });
}
