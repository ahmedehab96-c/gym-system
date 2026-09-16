import 'package:flutter_test/flutter_test.dart';
import 'package:gym_member_app/core/network/api_client.dart';
import 'package:gym_member_app/core/network/api_exception.dart';
import 'package:gym_member_app/core/network/api_result.dart';
import 'package:gym_member_app/core/network/cached_fetch.dart';
import 'package:gym_member_app/core/storage/cache_store.dart';
import 'package:gym_member_app/features/trainer_members/data/trainer_members_repository.dart';
import 'package:mocktail/mocktail.dart';

class MockApiClient extends Mock implements ApiClient {}

class MockCacheStore extends Mock implements CacheStore {}

void main() {
  late MockApiClient apiClient;
  late MockCacheStore cacheStore;
  late TrainerMembersRepository repository;

  setUp(() {
    apiClient = MockApiClient();
    cacheStore = MockCacheStore();
    when(() => cacheStore.write(any(), any(), any())).thenAnswer((_) async {});
    when(() => cacheStore.read(any(), any())).thenAnswer((_) async => null);
    repository = TrainerMembersRepository(apiClient, CachedFetch(cacheStore, 'trainer'));
  });

  test('assignedMembers() filters the existing /members endpoint by trainer_id', () async {
    when(() => apiClient.get('/members', query: any(named: 'query'))).thenAnswer((_) async => {
          'data': [
            {'id': 1, 'memberId': 'GYM-1', 'name': 'Alex', 'email': 'alex@example.com', 'status': 'Active'},
          ],
          'meta': {'page': 1, 'perPage': 15, 'total': 1, 'totalPages': 1},
        });

    final result = await repository.assignedMembers(7);

    result.when(
      success: (cached) => expect(cached.data.items, hasLength(1)),
      failure: (_) => fail('expected success'),
    );
    final captured = verify(() => apiClient.get('/members', query: captureAny(named: 'query'))).captured.single as Map;
    expect(captured['trainer_id'], 7);
  });

  test('assignedMembers() forwards a search term to the existing /members endpoint', () async {
    when(() => apiClient.get('/members', query: any(named: 'query'))).thenAnswer((_) async => {
          'data': [
            {'id': 2, 'memberId': 'GYM-2', 'name': 'Blair', 'email': 'blair@example.com', 'status': 'Active'},
          ],
          'meta': {'page': 1, 'perPage': 15, 'total': 1, 'totalPages': 1},
        });

    final result = await repository.assignedMembers(7, search: 'blair');

    result.when(
      success: (cached) => expect(cached.data.items.single.name, 'Blair'),
      failure: (_) => fail('expected success'),
    );
    final captured = verify(() => apiClient.get('/members', query: captureAny(named: 'query'))).captured.single as Map;
    expect(captured['search'], 'blair');
  });

  test('show() maps a cross-tenant/unauthorized 404 to a clean failure, never throws', () async {
    when(() => apiClient.get('/members/99')).thenThrow(const NotFoundException());

    final result = await repository.show(99);

    expect(result, isA<ApiFailure<dynamic>>());
  });

  test('memberAttendance() parses the paginated attendance history for one member', () async {
    when(() => apiClient.get('/members/1/attendance', query: any(named: 'query'))).thenAnswer((_) async => {
          'data': [
            {'id': 1, 'date': '2026-09-10', 'checkIn': '08:00', 'status': 'Checked In'},
          ],
          'meta': {'page': 1, 'perPage': 15, 'total': 1, 'totalPages': 1},
        });

    final result = await repository.memberAttendance(1);

    result.when(
      success: (cached) => expect(cached.data.items, hasLength(1)),
      failure: (_) => fail('expected success'),
    );
  });
}
