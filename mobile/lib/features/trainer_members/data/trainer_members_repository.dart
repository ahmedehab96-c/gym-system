import '../../../core/network/api_client.dart';
import '../../../core/network/api_result.dart';
import '../../../core/network/cached.dart';
import '../../../core/network/cached_fetch.dart';
import '../../../shared/models/attendance_record.dart';
import '../../../shared/models/member.dart';
import '../../../shared/models/paginated.dart';

/// Reuses the EXISTING staff /members and /members/{id}/attendance
/// endpoints (Phase 26 §6) — both already accept the filters/permissions
/// this needs (?trainer_id=, permission:Members / permission:Attendance
/// view access for the Trainer role), so nothing new was needed here.
/// All reads go through CachedFetch (Phase 27 §1 "Assigned members").
class TrainerMembersRepository {
  TrainerMembersRepository(this._apiClient, this._cachedFetch);

  final ApiClient _apiClient;
  final CachedFetch _cachedFetch;

  Future<ApiResult<Cached<Paginated<Member>>>> assignedMembers(int trainerId, {int page = 1, String? search}) {
    return _cachedFetch.call(
      // A search query is never cached under its own key — it's a
      // transient filter, not a distinct "assigned members" view worth
      // persisting offline; an offline search simply falls back to the
      // unfiltered list's cache instead of failing outright.
      cacheKey: 'assigned_members_p$page',
      fetchRaw: () => _apiClient.get('/members', query: {
        'trainer_id': trainerId,
        'page': page,
        if (search != null && search.isNotEmpty) 'search': search,
      }),
      parse: (raw) => Paginated.fromJson(raw, Member.fromJson),
    );
  }

  Future<ApiResult<Cached<Member>>> show(int memberId) {
    return _cachedFetch.call(
      cacheKey: 'member_show_$memberId',
      fetchRaw: () => _apiClient.get('/members/$memberId'),
      parse: (raw) => Member.fromJson(raw['data'] as Map<String, dynamic>),
    );
  }

  Future<ApiResult<Cached<Paginated<AttendanceRecord>>>> memberAttendance(int memberId, {int page = 1}) {
    return _cachedFetch.call(
      cacheKey: 'member_attendance_${memberId}_p$page',
      fetchRaw: () => _apiClient.get('/members/$memberId/attendance', query: {'page': page}),
      parse: (raw) => Paginated.fromJson(raw, AttendanceRecord.fromJson),
    );
  }
}
