import '../../../core/network/api_client.dart';
import '../../../core/network/api_exception.dart';
import '../../../core/network/api_result.dart';
import '../../../shared/models/attendance_record.dart';
import '../../../shared/models/paginated.dart';

/// Reuses the EXISTING staff /attendance/* endpoints (Phase 26 §5).
/// check_in/check_out are still gated server-side by permission:Attendance
/// create/edit — the Trainer role's default matrix has neither, so these
/// calls will 403 unless an admin grants it, which is correct: the
/// backend is the real enforcement point, this UI only *offers* the
/// action when StaffUser.permissions says it's actually usable.
class TrainerAttendanceRepository {
  TrainerAttendanceRepository(this._apiClient);

  final ApiClient _apiClient;

  Future<ApiResult<List<AttendanceRecord>>> today() async {
    try {
      final response = await _apiClient.get('/attendance/today');
      final items = (response['data'] as List? ?? []).map((e) => AttendanceRecord.fromJson(e as Map<String, dynamic>)).toList();
      return ApiSuccess(items);
    } on ApiException catch (e) {
      return ApiFailure(e);
    }
  }

  Future<ApiResult<Paginated<AttendanceRecord>>> history({int page = 1, String? date}) async {
    try {
      final response = await _apiClient.get('/attendance', query: {'page': page, 'date': ?date});
      return ApiSuccess(Paginated.fromJson(response, AttendanceRecord.fromJson));
    } on ApiException catch (e) {
      return ApiFailure(e);
    }
  }

  Future<ApiResult<AttendanceRecord>> checkIn(int memberId) async {
    try {
      final response = await _apiClient.post('/attendance/check-in', data: {'member_id': memberId});
      return ApiSuccess(AttendanceRecord.fromJson(response['data'] as Map<String, dynamic>));
    } on ApiException catch (e) {
      return ApiFailure(e);
    }
  }

  Future<ApiResult<AttendanceRecord>> checkOut(int attendanceId) async {
    try {
      final response = await _apiClient.post('/attendance/$attendanceId/check-out');
      return ApiSuccess(AttendanceRecord.fromJson(response['data'] as Map<String, dynamic>));
    } on ApiException catch (e) {
      return ApiFailure(e);
    }
  }
}
