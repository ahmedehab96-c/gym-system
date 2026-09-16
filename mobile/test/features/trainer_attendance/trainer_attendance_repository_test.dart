import 'package:flutter_test/flutter_test.dart';
import 'package:gym_member_app/core/network/api_client.dart';
import 'package:gym_member_app/core/network/api_exception.dart';
import 'package:gym_member_app/features/trainer_attendance/data/trainer_attendance_repository.dart';
import 'package:mocktail/mocktail.dart';

class MockApiClient extends Mock implements ApiClient {}

void main() {
  late MockApiClient apiClient;
  late TrainerAttendanceRepository repository;

  setUp(() {
    apiClient = MockApiClient();
    repository = TrainerAttendanceRepository(apiClient);
  });

  test("today() parses today's attendance records", () async {
    when(() => apiClient.get('/attendance/today')).thenAnswer((_) async => {
          'data': [
            {'id': 1, 'date': '2026-09-12', 'checkIn': '08:00', 'status': 'Checked In'},
          ],
        });

    final result = await repository.today();

    result.when(
      success: (records) => expect(records, hasLength(1)),
      failure: (_) => fail('expected success'),
    );
  });

  /// The Trainer role's default permission matrix has no `can_create` on
  /// Attendance — the backend must reject a check-in with 403 regardless
  /// of what the app's UI offers, and this repository must surface that
  /// as a clean ForbiddenException, not throw or silently succeed.
  test('checkIn() surfaces a 403 from the backend as a ForbiddenException failure', () async {
    when(() => apiClient.post('/attendance/check-in', data: any(named: 'data')))
        .thenThrow(const ForbiddenException('You do not have permission to perform this action.'));

    final result = await repository.checkIn(5);

    result.when(
      success: (_) => fail('expected failure'),
      failure: (error) => expect(error, isA<ForbiddenException>()),
    );
  });

  test('checkOut() parses the updated record on success', () async {
    when(() => apiClient.post('/attendance/1/check-out')).thenAnswer((_) async => {
          'data': {'id': 1, 'date': '2026-09-12', 'checkIn': '08:00', 'checkOut': '09:00', 'status': 'Checked Out'},
        });

    final result = await repository.checkOut(1);

    result.when(
      success: (record) => expect(record.checkOut, '09:00'),
      failure: (_) => fail('expected success'),
    );
  });
}
