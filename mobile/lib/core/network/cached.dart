/// Describes where a `Cached<T>` value came from — screens use this to
/// decide whether to show a "showing cached data" indicator (Phase 27
/// §3: "clearly indicate when data is cached/stale").
class CacheMeta {
  const CacheMeta.live() : isFromCache = false, cachedAt = null;

  const CacheMeta.cached(DateTime this.cachedAt) : isFromCache = true;

  final bool isFromCache;
  final DateTime? cachedAt;
}

/// Wraps any repository read result with where it came from. Every
/// cache-eligible repository method returns `ApiResult<Cached<T>>`
/// instead of `ApiResult<T>` so the UI layer always has the staleness
/// info available without guessing.
class Cached<T> {
  const Cached(this.data, this.meta);

  final T data;
  final CacheMeta meta;
}
