import '../../../core/network/api_client.dart';
import '../../../core/network/api_exception.dart';
import '../../../core/network/api_result.dart';
import '../../../shared/models/paginated.dart';
import '../../../shared/models/trainer.dart';

class TrainersRepository {
  TrainersRepository(this._apiClient);

  final ApiClient _apiClient;

  Future<ApiResult<Paginated<Trainer>>> list({int page = 1}) async {
    try {
      final response = await _apiClient.get('/member/trainers', query: {'page': page});
      return ApiSuccess(Paginated.fromJson(response, Trainer.fromJson));
    } on ApiException catch (e) {
      return ApiFailure(e);
    }
  }

  Future<ApiResult<Trainer>> show(int id) async {
    try {
      final response = await _apiClient.get('/member/trainers/$id');
      return ApiSuccess(Trainer.fromJson(response['data'] as Map<String, dynamic>));
    } on ApiException catch (e) {
      return ApiFailure(e);
    }
  }
}
