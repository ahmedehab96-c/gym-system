import '../../../core/network/api_client.dart';
import '../../../core/network/api_result.dart';
import '../../../core/network/cached.dart';
import '../../../core/network/cached_fetch.dart';
import '../../../shared/models/attendance_record.dart';
import '../../../shared/models/paginated.dart';

class AttendanceRepository {
  AttendanceRepository(this._apiClient, this._cachedFetch);

  final ApiClient _apiClient;
  final CachedFetch _cachedFetch;

  Future<ApiResult<Cached<Paginated<AttendanceRecord>>>> history({int page = 1}) {
    return _cachedFetch.call(
      cacheKey: 'attendance_history_p$page',
      fetchRaw: () => _apiClient.get('/member/attendance', query: {'page': page}),
      parse: (raw) => Paginated.fromJson(raw, AttendanceRecord.fromJson),
    );
  }

  Future<ApiResult<Cached<AttendanceSummary>>> summary() {
    return _cachedFetch.call(
      cacheKey: 'attendance_summary',
      fetchRaw: () => _apiClient.get('/member/attendance/summary'),
      parse: (raw) => AttendanceSummary.fromJson(raw['data'] as Map<String, dynamic>),
    );
  }
}
