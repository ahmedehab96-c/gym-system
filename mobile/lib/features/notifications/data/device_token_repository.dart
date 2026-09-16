import '../../../core/network/api_client.dart';
import '../../../core/network/api_exception.dart';
import '../../../core/network/api_result.dart';

/// Registers this device for push notifications against the existing
/// backend communication architecture (Phase 24's device_tokens table,
/// Phase 25 §9). No push SDK (Firebase Messaging etc.) is wired up yet —
/// that requires a Firebase project/credentials this environment doesn't
/// have — so nothing calls `register()` automatically today. This is the
/// prepared integration point: once a push SDK is added, its token
/// callback calls straight into this, with no other app-layer changes.
class DeviceTokenRepository {
  DeviceTokenRepository(this._apiClient);

  final ApiClient _apiClient;

  Future<ApiResult<void>> register({required String token, required String platform}) async {
    try {
      await _apiClient.post('/member/device-tokens', data: {'token': token, 'platform': platform});
      return const ApiSuccess(null);
    } on ApiException catch (e) {
      return ApiFailure(e);
    }
  }

  Future<ApiResult<void>> unregister(int deviceTokenId) async {
    try {
      await _apiClient.delete('/member/device-tokens/$deviceTokenId');
      return const ApiSuccess(null);
    } on ApiException catch (e) {
      return ApiFailure(e);
    }
  }
}
