import '../../../core/network/api_client.dart';
import '../../../core/network/api_result.dart';
import '../../../core/network/cached.dart';
import '../../../core/network/cached_fetch.dart';
import '../../../shared/models/paginated.dart';
import '../../../shared/models/training_program.dart';

class ProgramsRepository {
  ProgramsRepository(this._apiClient, this._cachedFetch);

  final ApiClient _apiClient;
  final CachedFetch _cachedFetch;

  Future<ApiResult<Cached<Paginated<TrainingProgram>>>> list({int page = 1}) {
    return _cachedFetch.call(
      cacheKey: 'programs_list_p$page',
      fetchRaw: () => _apiClient.get('/member/programs', query: {'page': page}),
      parse: (raw) => Paginated.fromJson(raw, TrainingProgram.fromJson),
    );
  }

  Future<ApiResult<Cached<List<TrainingProgram>>>> mine() {
    return _cachedFetch.call(
      cacheKey: 'programs_mine',
      fetchRaw: () => _apiClient.get('/member/programs/mine'),
      parse: (raw) => (raw['data'] as List? ?? []).map((e) => TrainingProgram.fromJson(e as Map<String, dynamic>)).toList(),
    );
  }

  Future<ApiResult<Cached<TrainingProgram>>> show(int id) {
    return _cachedFetch.call(
      cacheKey: 'programs_show_$id',
      fetchRaw: () => _apiClient.get('/member/programs/$id'),
      parse: (raw) => TrainingProgram.fromJson(raw['data'] as Map<String, dynamic>),
    );
  }
}
