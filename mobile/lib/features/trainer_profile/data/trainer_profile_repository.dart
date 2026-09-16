import '../../../core/network/api_client.dart';
import '../../../core/network/api_exception.dart';
import '../../../core/network/api_result.dart';
import '../../../core/network/cached.dart';
import '../../../core/network/cached_fetch.dart';
import '../../../shared/models/trainer.dart';

class TrainerProfileRepository {
  TrainerProfileRepository(this._apiClient, this._cachedFetch);

  final ApiClient _apiClient;
  final CachedFetch _cachedFetch;

  Future<ApiResult<Cached<Trainer>>> show() {
    return _cachedFetch.call(
      cacheKey: 'trainer_roster_profile',
      fetchRaw: () => _apiClient.get('/trainer/profile'),
      parse: (raw) => Trainer.fromJson(raw['data'] as Map<String, dynamic>),
    );
  }

  Future<ApiResult<Trainer>> update({
    String? name,
    String? specialty,
    String? experience,
    String? phone,
    String? bio,
  }) async {
    try {
      final response = await _apiClient.put('/trainer/profile', data: {
        'name': ?name,
        'specialty': ?specialty,
        'experience': ?experience,
        'phone': ?phone,
        'bio': ?bio,
      });
      return ApiSuccess(Trainer.fromJson(response['data'] as Map<String, dynamic>));
    } on ApiException catch (e) {
      return ApiFailure(e);
    }
  }

  Future<ApiResult<Trainer>> uploadPhoto(String filePath) async {
    try {
      final response = await _apiClient.uploadFile('/trainer/profile/photo', fieldName: 'photo', filePath: filePath);
      return ApiSuccess(Trainer.fromJson(response['data'] as Map<String, dynamic>));
    } on ApiException catch (e) {
      return ApiFailure(e);
    }
  }
}
