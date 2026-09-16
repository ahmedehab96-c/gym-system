import '../../../core/network/api_client.dart';
import '../../../core/network/api_result.dart';
import '../../../core/network/cached.dart';
import '../../../core/network/cached_fetch.dart';
import '../../../shared/models/paginated.dart';
import '../../../shared/models/training_program.dart';

/// Reuses the EXISTING staff /training-programs endpoints (Phase 26 §7)
/// — already accepts ?trainer_id=, already view-permitted for the
/// Trainer role. Exercises/workout details aren't in the current
/// TrainingProgram schema (only name/description/duration/difficulty),
/// so this only ever shows what actually exists — see the model. Both
/// reads go through CachedFetch (Phase 27 §1 "Training programs").
class TrainerProgramsRepository {
  TrainerProgramsRepository(this._apiClient, this._cachedFetch);

  final ApiClient _apiClient;
  final CachedFetch _cachedFetch;

  Future<ApiResult<Cached<Paginated<TrainingProgram>>>> assignedPrograms(int trainerId, {int page = 1}) {
    return _cachedFetch.call(
      cacheKey: 'assigned_programs_p$page',
      fetchRaw: () => _apiClient.get('/training-programs', query: {'trainer_id': trainerId, 'page': page}),
      parse: (raw) => Paginated.fromJson(raw, TrainingProgram.fromJson),
    );
  }

  Future<ApiResult<Cached<TrainingProgram>>> show(int programId) {
    return _cachedFetch.call(
      cacheKey: 'program_show_$programId',
      fetchRaw: () => _apiClient.get('/training-programs/$programId'),
      parse: (raw) => TrainingProgram.fromJson(raw['data'] as Map<String, dynamic>),
    );
  }
}
