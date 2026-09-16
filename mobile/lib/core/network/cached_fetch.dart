import '../storage/cache_store.dart';
import 'api_exception.dart';
import 'api_result.dart';
import 'cached.dart';

/// Network-first, cache-fallback helper shared by every read-heavy
/// repository method that supports offline reads (Phase 27 §1/§3). A
/// repository calls this instead of hand-rolling try/catch + cache
/// read/write itself:
///
/// ```dart
/// Future<ApiResult<Cached<Paginated<GymClass>>>> list({int page = 1}) {
///   return _cachedFetch.call(
///     cacheKey: 'classes_list_p$page',
///     fetchRaw: () => _apiClient.get('/member/classes', query: {'page': page}),
///     parse: (raw) => Paginated.fromJson(raw, GymClass.fromJson),
///   );
/// }
/// ```
///
/// On success the raw response envelope is cached and returned as
/// "live"; on a NetworkException the last-cached envelope (if any) is
/// parsed and returned as "cached" instead of surfacing the error — any
/// other failure (401/403/404/422/500) is never masked by a cache
/// fallback, since those are real server answers, not connectivity gaps.
class CachedFetch {
  CachedFetch(this._cacheStore, this._namespace);

  final CacheStore _cacheStore;
  final String _namespace;

  Future<ApiResult<Cached<T>>> call<T>({
    required String cacheKey,
    required Future<Map<String, dynamic>> Function() fetchRaw,
    required T Function(Map<String, dynamic> raw) parse,
  }) async {
    try {
      final raw = await fetchRaw();
      await _cacheStore.write(_namespace, cacheKey, raw);
      return ApiSuccess(Cached(parse(raw), const CacheMeta.live()));
    } on ApiException catch (e) {
      if (e is NetworkException) {
        final entry = await _cacheStore.read(_namespace, cacheKey);
        if (entry != null) {
          return ApiSuccess(Cached(parse(entry.json), CacheMeta.cached(entry.cachedAt)));
        }
      }
      return ApiFailure(e);
    }
  }
}
