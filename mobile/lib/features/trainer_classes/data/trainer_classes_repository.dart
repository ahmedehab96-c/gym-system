import '../../../core/network/api_client.dart';
import '../../../core/network/api_result.dart';
import '../../../core/network/cached.dart';
import '../../../core/network/cached_fetch.dart';
import '../../../shared/models/gym_class.dart';
import '../../../shared/models/paginated.dart';
import 'schedule_models.dart';

/// Reuses the EXISTING staff /classes and /schedule/* endpoints (Phase
/// 26 §3) — both already accept a `trainer_id` filter, and the Trainer
/// role already has view permission on the Classes module, so nothing
/// new was needed on the backend for this. All reads go through
/// CachedFetch (Phase 27 §1 "My classes/schedule").
class TrainerClassesRepository {
  TrainerClassesRepository(this._apiClient, this._cachedFetch);

  final ApiClient _apiClient;
  final CachedFetch _cachedFetch;

  Future<ApiResult<Cached<Paginated<GymClass>>>> myClasses(int trainerId, {int page = 1, String? search}) {
    final trimmed = search?.trim();
    return _cachedFetch.call(
      cacheKey: trimmed == null || trimmed.isEmpty ? 'my_classes_p$page' : 'my_classes_search_${trimmed}_p$page',
      fetchRaw: () => _apiClient.get('/classes', query: {
        'trainer_id': trainerId,
        'page': page,
        if (trimmed != null && trimmed.isNotEmpty) 'search': trimmed,
      }),
      parse: (raw) => Paginated.fromJson(raw, GymClass.fromJson),
    );
  }

  Future<ApiResult<Cached<GymClass>>> show(int classId) {
    return _cachedFetch.call(
      cacheKey: 'class_show_$classId',
      fetchRaw: () => _apiClient.get('/classes/$classId'),
      parse: (raw) => GymClass.fromJson(raw['data'] as Map<String, dynamic>),
    );
  }

  Future<ApiResult<Cached<ScheduleDay>>> daily(int trainerId, {String? date}) {
    return _cachedFetch.call(
      cacheKey: 'schedule_daily_${date ?? 'today'}',
      fetchRaw: () => _apiClient.get('/schedule/daily', query: {'trainer_id': trainerId, 'date': ?date}),
      parse: (raw) => ScheduleDay.fromJson(raw['data'] as Map<String, dynamic>),
    );
  }

  Future<ApiResult<Cached<WeeklySchedule>>> weekly(int trainerId, {String? date}) {
    return _cachedFetch.call(
      cacheKey: 'schedule_weekly_${date ?? 'current'}',
      fetchRaw: () => _apiClient.get('/schedule/weekly', query: {'trainer_id': trainerId, 'date': ?date}),
      parse: (raw) => WeeklySchedule.fromJson(raw['data'] as Map<String, dynamic>),
    );
  }

  Future<ApiResult<Cached<MonthlySchedule>>> monthly(int trainerId, {String? month}) {
    return _cachedFetch.call(
      cacheKey: 'schedule_monthly_${month ?? 'current'}',
      fetchRaw: () => _apiClient.get('/schedule/monthly', query: {'trainer_id': trainerId, 'month': ?month}),
      parse: (raw) => MonthlySchedule.fromJson(raw['data'] as Map<String, dynamic>),
    );
  }
}
