import '../../../core/network/api_client.dart';
import '../../../core/network/api_exception.dart';
import '../../../core/network/api_result.dart';
import '../../../shared/models/member.dart';

class ProfileRepository {
  ProfileRepository(this._apiClient);

  final ApiClient _apiClient;

  Future<ApiResult<Member>> update({
    String? name,
    String? phone,
    String? address,
    String? gender,
    String? dob,
    String? emergencyContact,
  }) async {
    try {
      final response = await _apiClient.put('/member/profile', data: {
        'name': ?name,
        'phone': ?phone,
        'address': ?address,
        'gender': ?gender,
        'dob': ?dob,
        'emergency_contact': ?emergencyContact,
      });
      return ApiSuccess(Member.fromJson(response['data'] as Map<String, dynamic>));
    } on ApiException catch (e) {
      return ApiFailure(e);
    }
  }

  Future<ApiResult<Member>> uploadPhoto(String filePath) async {
    try {
      final response = await _apiClient.uploadFile('/member/profile/photo', fieldName: 'photo', filePath: filePath);
      return ApiSuccess(Member.fromJson(response['data'] as Map<String, dynamic>));
    } on ApiException catch (e) {
      return ApiFailure(e);
    }
  }
}
