import '../../../core/network/api_client.dart';
import '../../../core/network/api_exception.dart';
import '../../../core/network/api_result.dart';
import '../../../core/network/cached.dart';
import '../../../core/network/cached_fetch.dart';
import '../../../shared/models/app_notification.dart';
import '../../../shared/models/paginated.dart';

class NotificationsRepository {
  NotificationsRepository(this._apiClient, this._cachedFetch);

  final ApiClient _apiClient;
  final CachedFetch _cachedFetch;

  Future<ApiResult<Cached<Paginated<AppNotification>>>> list({int page = 1}) {
    return _cachedFetch.call(
      cacheKey: 'notifications_list_p$page',
      fetchRaw: () => _apiClient.get('/member/notifications', query: {'page': page}),
      parse: (raw) => Paginated.fromJson(raw, AppNotification.fromJson),
    );
  }

  Future<ApiResult<AppNotification>> markRead(int id) async {
    try {
      final response = await _apiClient.patch('/member/notifications/$id/read');
      return ApiSuccess(AppNotification.fromJson(response['data'] as Map<String, dynamic>));
    } on ApiException catch (e) {
      return ApiFailure(e);
    }
  }

  Future<ApiResult<void>> markAllRead() async {
    try {
      await _apiClient.post('/member/notifications/mark-all-read');
      return const ApiSuccess(null);
    } on ApiException catch (e) {
      return ApiFailure(e);
    }
  }
}
