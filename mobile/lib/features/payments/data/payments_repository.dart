import '../../../core/network/api_client.dart';
import '../../../core/network/api_exception.dart';
import '../../../core/network/api_result.dart';
import '../../../shared/models/invoice.dart';
import '../../../shared/models/paginated.dart';
import '../../../shared/models/payment.dart';

class PaymentsRepository {
  PaymentsRepository(this._apiClient);

  final ApiClient _apiClient;

  Future<ApiResult<Paginated<Payment>>> payments({int page = 1}) async {
    try {
      final response = await _apiClient.get('/member/payments', query: {'page': page});
      return ApiSuccess(Paginated.fromJson(response, Payment.fromJson));
    } on ApiException catch (e) {
      return ApiFailure(e);
    }
  }

  Future<ApiResult<Paginated<Invoice>>> invoices({int page = 1}) async {
    try {
      final response = await _apiClient.get('/member/invoices', query: {'page': page});
      return ApiSuccess(Paginated.fromJson(response, Invoice.fromJson));
    } on ApiException catch (e) {
      return ApiFailure(e);
    }
  }

  Future<ApiResult<Invoice>> invoiceDetail(int id) async {
    try {
      final response = await _apiClient.get('/member/invoices/$id');
      return ApiSuccess(Invoice.fromJson(response['data'] as Map<String, dynamic>));
    } on ApiException catch (e) {
      return ApiFailure(e);
    }
  }
}
