import 'package:flutter_test/flutter_test.dart';
import 'package:gym_member_app/core/network/api_client.dart';
import 'package:gym_member_app/core/network/cached_fetch.dart';
import 'package:gym_member_app/core/storage/cache_store.dart';
import 'package:gym_member_app/features/attendance/data/attendance_repository.dart';
import 'package:mocktail/mocktail.dart';

class MockApiClient extends Mock implements ApiClient {}

class MockCacheStore extends Mock implements CacheStore {}

void main() {
  late MockApiClient apiClient;
  late MockCacheStore cacheStore;
  late AttendanceRepository repository;

  setUp(() {
    apiClient = MockApiClient();
    cacheStore = MockCacheStore();
    when(() => cacheStore.write(any(), any(), any())).thenAnswer((_) async {});
    repository = AttendanceRepository(apiClient, CachedFetch(cacheStore, 'member'));
  });

  test('history() parses attendance records with derived check-in status', () async {
    when(() => apiClient.get('/member/attendance', query: any(named: 'query'))).thenAnswer((_) async => {
          'data': [
            {'id': 1, 'date': '2026-09-01', 'checkIn': '08:00', 'checkOut': '09:15', 'status': 'Checked Out'},
          ],
          'meta': {'page': 1, 'perPage': 20, 'total': 1, 'totalPages': 1},
        });

    final result = await repository.history();

    result.when(
      success: (cached) {
        expect(cached.meta.isFromCache, isFalse);
        expect(cached.data.items, hasLength(1));
        expect(cached.data.items.first.status, 'Checked Out');
      },
      failure: (_) => fail('expected success'),
    );
  });

  test('summary() parses the aggregated stats block', () async {
    when(() => apiClient.get('/member/attendance/summary')).thenAnswer((_) async => {
          'data': {
            'visitsThisMonth': 8,
            'totalVisits': 42,
            'lastVisitDate': '2026-09-10',
            'currentlyCheckedIn': false,
            'attendanceRate': 90,
          },
        });

    final result = await repository.summary();

    result.when(
      success: (cached) {
        expect(cached.data.visitsThisMonth, 8);
        expect(cached.data.attendanceRate, 90);
        expect(cached.data.currentlyCheckedIn, isFalse);
      },
      failure: (_) => fail('expected success'),
    );
  });
}
