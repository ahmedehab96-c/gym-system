import '../../../core/network/api_client.dart';
import '../../../core/network/api_exception.dart';
import '../../../core/network/api_result.dart';
import '../../../core/network/cached.dart';
import '../../../core/network/cached_fetch.dart';
import '../../../core/storage/cache_store.dart';
import '../../../core/storage/secure_storage.dart';
import '../../../shared/models/staff_user.dart';

/// Talks to the EXISTING staff /auth/* endpoints (Phase 26 §1) — a
/// trainer is a normal staff User, not a new actor type, so this reuses
/// the same login/logout/me contract the React admin dashboard already
/// uses. No new backend auth surface — only ApiClient/SecureStorage,
/// already shared with the Member app, are reused here.
class TrainerAuthRepository {
  TrainerAuthRepository({
    required this._apiClient,
    required this._secureStorage,
    required this._cacheStore,
    required this._cachedFetch,
  });

  final ApiClient _apiClient;
  final SecureStorage _secureStorage;
  final CacheStore _cacheStore;
  final CachedFetch _cachedFetch;

  Future<ApiResult<StaffUser>> login({required String email, required String password}) async {
    try {
      final response = await _apiClient.post('/auth/login', data: {'email': email, 'password': password});
      final data = response['data'] as Map<String, dynamic>;
      final token = data['token'] as String;
      // Phase 27: wipe a previous trainer session's cache on this device
      // before this one's token is written — see AuthRepository.login().
      await _cacheStore.clearNamespace('trainer');
      await _secureStorage.saveToken(token);
      await _secureStorage.saveActorType('trainer');
      return ApiSuccess(StaffUser.fromJson(data['user'] as Map<String, dynamic>));
    } on ApiException catch (e) {
      return ApiFailure(e);
    }
  }

  /// Cached (Phase 27 §1 "Profile") — see AuthRepository.currentMember()
  /// for why.
  Future<ApiResult<Cached<StaffUser>>> currentUser() {
    return _cachedFetch.call(
      cacheKey: 'profile',
      fetchRaw: () => _apiClient.get('/auth/me'),
      parse: (raw) => StaffUser.fromJson(raw['data'] as Map<String, dynamic>),
    );
  }

  Future<void> logout() async {
    try {
      await _apiClient.post('/auth/logout');
    } on ApiException {
      // Best-effort, same as the Member app's logout — see AuthRepository.
    } finally {
      await _secureStorage.clearToken();
      await _secureStorage.clearActorType();
      await _cacheStore.clearNamespace('trainer');
    }
  }

  Future<bool> hasStoredTrainerSession() async {
    final actorType = await _secureStorage.readActorType();
    final token = await _secureStorage.readToken();
    return actorType == 'trainer' && token != null;
  }

  Future<void> clearLocalSession() async {
    await _secureStorage.clearToken();
    await _secureStorage.clearActorType();
    await _cacheStore.clearNamespace('trainer');
  }
}
