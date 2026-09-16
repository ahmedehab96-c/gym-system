import '../../../core/network/api_client.dart';
import '../../../core/network/api_exception.dart';
import '../../../core/network/api_result.dart';
import '../../../core/network/cached.dart';
import '../../../core/network/cached_fetch.dart';
import '../../../shared/models/gym_class.dart';
import '../../../shared/models/paginated.dart';

/// Phase 27 §1: classes/schedule are read-heavy and cache-eligible, so
/// `list`/`myBookings`/`show` go through CachedFetch and fall back to the
/// last-cached page on a NetworkException. `book`/`cancel` are mutations
/// — never served from cache — but ARE safe to queue while offline (see
/// OfflineActionKind's docblock for why retrying them can't double-book).
class ClassesRepository {
  ClassesRepository(this._apiClient, this._cachedFetch);

  final ApiClient _apiClient;
  final CachedFetch _cachedFetch;

  Future<ApiResult<Cached<Paginated<GymClass>>>> list({int page = 1, String? search}) {
    final trimmed = search?.trim();
    return _cachedFetch.call(
      // A search result is intentionally its own cache entry (not merged
      // into the unfiltered list's) — see CachedFetch's docblock: each
      // cacheKey is a distinct offline snapshot.
      cacheKey: trimmed == null || trimmed.isEmpty ? 'classes_list_p$page' : 'classes_search_${trimmed}_p$page',
      fetchRaw: () => _apiClient.get('/member/classes', query: {'page': page, if (trimmed != null && trimmed.isNotEmpty) 'search': trimmed}),
      parse: (raw) => Paginated.fromJson(raw, GymClass.fromJson),
    );
  }

  Future<ApiResult<Cached<Paginated<GymClass>>>> myBookings({int page = 1}) {
    return _cachedFetch.call(
      cacheKey: 'classes_my_bookings_p$page',
      fetchRaw: () => _apiClient.get('/member/classes/my-bookings', query: {'page': page}),
      parse: (raw) => Paginated.fromJson(raw, GymClass.fromJson),
    );
  }

  Future<ApiResult<Cached<GymClass>>> show(int id) {
    return _cachedFetch.call(
      cacheKey: 'classes_show_$id',
      fetchRaw: () => _apiClient.get('/member/classes/$id'),
      parse: (raw) => GymClass.fromJson(raw['data'] as Map<String, dynamic>),
    );
  }

  Future<ApiResult<GymClass>> book(int id) async {
    try {
      final response = await _apiClient.post('/member/classes/$id/book');
      return ApiSuccess(GymClass.fromJson(response['data'] as Map<String, dynamic>));
    } on ApiException catch (e) {
      return ApiFailure(e);
    }
  }

  Future<ApiResult<GymClass>> cancel(int id) async {
    try {
      final response = await _apiClient.delete('/member/classes/$id/book');
      return ApiSuccess(GymClass.fromJson(response['data'] as Map<String, dynamic>));
    } on ApiException catch (e) {
      return ApiFailure(e);
    }
  }
}
