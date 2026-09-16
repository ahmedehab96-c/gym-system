import '../../../core/network/api_client.dart';
import '../../../core/network/api_result.dart';
import '../../../core/network/api_exception.dart';
import '../../../core/network/cached.dart';
import '../../../core/network/cached_fetch.dart';
import '../../../core/storage/cache_store.dart';
import '../../../core/storage/secure_storage.dart';
import '../../../shared/models/member.dart';

/// Talks to /member/auth/* only — every other feature's repository talks
/// to its own slice of the API. Owns the token lifecycle end-to-end:
/// login writes it to SecureStorage, logout clears it, nothing else in
/// the app touches SecureStorage's token methods directly.
class AuthRepository {
  AuthRepository({
    required this._apiClient,
    required this._secureStorage,
    required this._cacheStore,
    required this._cachedFetch,
  });

  final ApiClient _apiClient;
  final SecureStorage _secureStorage;
  final CacheStore _cacheStore;
  final CachedFetch _cachedFetch;

  Future<ApiResult<Member>> login({required String email, required String password}) async {
    try {
      final response = await _apiClient.post('/member/auth/login', data: {
        'email': email,
        'password': password,
      });
      final data = response['data'] as Map<String, dynamic>;
      final token = data['token'] as String;
      // Phase 27: wipe anything a previous member session on this device
      // cached before writing this one's token, so a fresh login never
      // briefly shows someone else's classes/attendance/notifications.
      await _cacheStore.clearNamespace('member');
      await _secureStorage.saveToken(token);
      // Tags this session as a Member session (Phase 26 introduced a
      // second, Trainer, actor type sharing the same token slot — see
      // SecureStorage's docblock).
      await _secureStorage.saveActorType('member');
      return ApiSuccess(Member.fromJson(data['member'] as Map<String, dynamic>));
    } on ApiException catch (e) {
      return ApiFailure(e);
    }
  }

  /// Cached (Phase 27 §1 "Profile") so `restoreSession` can still show a
  /// signed-in member their last-known profile when the app is reopened
  /// offline, instead of always requiring a fresh network round trip.
  Future<ApiResult<Cached<Member>>> currentMember() {
    return _cachedFetch.call(
      cacheKey: 'profile',
      fetchRaw: () => _apiClient.get('/member/auth/me'),
      parse: (raw) => Member.fromJson(raw['data'] as Map<String, dynamic>),
    );
  }

  /// Best-effort — the token is cleared locally regardless of whether the
  /// server call succeeds, so a member is never stuck "logged in" on the
  /// device just because the revoke request failed (e.g. offline).
  Future<void> logout() async {
    try {
      await _apiClient.post('/member/auth/logout');
    } on ApiException {
      // Ignored deliberately — see docblock above.
    } finally {
      await _secureStorage.clearToken();
      await _secureStorage.clearActorType();
      await _cacheStore.clearNamespace('member');
    }
  }

  Future<bool> hasStoredSession() async => (await _secureStorage.readToken()) != null;

  Future<void> clearLocalSession() async {
    await _secureStorage.clearToken();
    await _secureStorage.clearActorType();
    await _cacheStore.clearNamespace('member');
  }
}
