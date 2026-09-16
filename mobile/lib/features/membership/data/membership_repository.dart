import '../../../core/network/api_client.dart';
import '../../../core/network/api_result.dart';
import '../../../core/network/cached.dart';
import '../../../core/network/cached_fetch.dart';
import '../../../shared/models/membership.dart';
import '../../../shared/models/paginated.dart';

/// `current()`/`history()` describe a member's plan (name/status/dates,
/// a static plan price) — not a live transaction or amount owed, so
/// caching it for offline viewing carries none of the "confirmed live
/// financial state" risk Phase 27 §3 warns about. Actual payment/invoice
/// data (features/payments) is deliberately never cached — see this
/// phase's own scope, which never lists Payments among the cache-eligible
/// data.
class MembershipRepository {
  MembershipRepository(this._apiClient, this._cachedFetch);

  final ApiClient _apiClient;
  final CachedFetch _cachedFetch;

  Future<ApiResult<Cached<Membership>>> current() {
    return _cachedFetch.call(
      cacheKey: 'membership_current',
      fetchRaw: () => _apiClient.get('/member/membership'),
      parse: (raw) => Membership.fromJson(raw['data'] as Map<String, dynamic>),
    );
  }

  Future<ApiResult<Cached<Paginated<Membership>>>> history({int page = 1}) {
    return _cachedFetch.call(
      cacheKey: 'membership_history_p$page',
      fetchRaw: () => _apiClient.get('/member/membership/history', query: {'page': page}),
      parse: (raw) => Paginated.fromJson(raw, Membership.fromJson),
    );
  }
}
