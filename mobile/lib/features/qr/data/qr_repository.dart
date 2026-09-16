import '../../../core/network/api_client.dart';
import '../../../core/network/api_exception.dart';
import '../../../core/network/api_result.dart';
import 'member_qr.dart';

/// Talks to the member's own `/member/qr` self-service endpoints only
/// (Phase 28 §1). Deliberately NOT cached (Phase 27's CachedFetch) — the
/// token must always reflect the server's current one; showing a stale
/// cached token offline would let a front desk scan a code the backend
/// already invalidated.
class QrRepository {
  QrRepository(this._apiClient);

  final ApiClient _apiClient;

  Future<ApiResult<MemberQr>> show() async {
    try {
      final response = await _apiClient.get('/member/qr');
      return ApiSuccess(MemberQr.fromJson(response['data'] as Map<String, dynamic>));
    } on ApiException catch (e) {
      return ApiFailure(e);
    }
  }

  Future<ApiResult<MemberQr>> regenerate() async {
    try {
      final response = await _apiClient.post('/member/qr/regenerate');
      return ApiSuccess(MemberQr.fromJson(response['data'] as Map<String, dynamic>));
    } on ApiException catch (e) {
      return ApiFailure(e);
    }
  }
}
