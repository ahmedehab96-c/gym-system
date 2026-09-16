import '../../../core/network/api_client.dart';
import '../../../core/network/api_exception.dart';
import '../../../core/network/api_result.dart';
import '../../../core/network/cached.dart';
import '../../../core/network/cached_fetch.dart';
import '../../../shared/models/app_notification.dart';
import '../../../shared/models/paginated.dart';

/// Reuses the EXISTING staff /notifications endpoints as-is (Phase 26
/// §8) — App\Http\Controllers\Api\NotificationController already scopes
/// a user's inbox to their own user_id + global notifications, with no
/// permission gate at all, so nothing changed on the backend for this.
class TrainerNotificationsRepository {
  TrainerNotificationsRepository(this._apiClient, this._cachedFetch);

  final ApiClient _apiClient;
  final CachedFetch _cachedFetch;

  Future<ApiResult<Cached<Paginated<AppNotification>>>> list({int page = 1, bool? read}) {
    return _cachedFetch.call(
      cacheKey: 'trainer_notifications_list_p$page',
      fetchRaw: () => _apiClient.get('/notifications', query: {'page': page, 'read': ?read}),
      parse: (raw) => Paginated.fromJson(raw, AppNotification.fromJson),
    );
  }

  Future<ApiResult<AppNotification>> markRead(int id) async {
    try {
      final response = await _apiClient.patch('/notifications/$id', data: {'read': true});
      return ApiSuccess(AppNotification.fromJson(response['data'] as Map<String, dynamic>));
    } on ApiException catch (e) {
      return ApiFailure(e);
    }
  }

  Future<ApiResult<void>> markAllRead() async {
    try {
      await _apiClient.post('/notifications/mark-all-read');
      return const ApiSuccess(null);
    } on ApiException catch (e) {
      return ApiFailure(e);
    }
  }
}
