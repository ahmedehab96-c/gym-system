import 'package:dio/dio.dart';

import '../config/app_config.dart';
import '../storage/secure_storage.dart';
import 'api_exception.dart';

/// Thin wrapper around Dio — the single place that knows the Laravel API's
/// URL, auth header, and response envelope shape ({"data": ...},
/// {"data": [...], "meta": {...}}, {"message": ..., "errors": {...}}).
/// Every feature repository goes through this; none of them import `dio`
/// directly (Phase 25 §1/§11 — centralized API service layer).
class ApiClient {
  ApiClient({required this._secureStorage, Dio? dio})
      : _dio = dio ??
            Dio(BaseOptions(
              baseUrl: AppConfig.apiBaseUrl,
              connectTimeout: AppConfig.connectTimeout,
              receiveTimeout: AppConfig.receiveTimeout,
              headers: {'Accept': 'application/json'},
            )) {
    _dio.interceptors.add(InterceptorsWrapper(onRequest: _onRequest));
  }

  final Dio _dio;
  final SecureStorage _secureStorage;

  /// Called whenever a request comes back 401 — AuthProvider subscribes to
  /// this to force a logout + redirect, rather than every screen having to
  /// check for it individually.
  void Function()? onUnauthorized;

  Future<void> _onRequest(RequestOptions options, RequestInterceptorHandler handler) async {
    final token = await _secureStorage.readToken();
    if (token != null) {
      options.headers['Authorization'] = 'Bearer $token';
    }
    handler.next(options);
  }

  Future<Map<String, dynamic>> get(String path, {Map<String, dynamic>? query}) =>
      _request(() => _dio.get(path, queryParameters: query));

  Future<Map<String, dynamic>> post(String path, {Object? data}) => _request(() => _dio.post(path, data: data));

  Future<Map<String, dynamic>> put(String path, {Object? data}) => _request(() => _dio.put(path, data: data));

  Future<Map<String, dynamic>> patch(String path, {Object? data}) => _request(() => _dio.patch(path, data: data));

  Future<Map<String, dynamic>> delete(String path) => _request(() => _dio.delete(path));

  /// Multipart upload (profile photo) — a plain file path in, the same
  /// envelope shape out as every other call.
  Future<Map<String, dynamic>> uploadFile(String path, {required String fieldName, required String filePath}) {
    final formData = FormData.fromMap({fieldName: MultipartFile.fromFileSync(filePath)});
    return _request(() => _dio.post(path, data: formData));
  }

  Future<Map<String, dynamic>> _request(Future<Response<dynamic>> Function() call) async {
    try {
      final response = await call();
      final body = response.data;
      if (body is Map<String, dynamic>) return body;
      return <String, dynamic>{'data': body};
    } on DioException catch (e) {
      throw _mapError(e);
    }
  }

  ApiException _mapError(DioException e) {
    if (e.type == DioExceptionType.connectionError ||
        e.type == DioExceptionType.connectionTimeout ||
        e.type == DioExceptionType.receiveTimeout ||
        e.type == DioExceptionType.sendTimeout) {
      return const NetworkException();
    }

    final status = e.response?.statusCode;
    final body = e.response?.data;
    final message = (body is Map && body['message'] is String) ? body['message'] as String : null;

    switch (status) {
      case 401:
        onUnauthorized?.call();
        return UnauthorizedException(message ?? 'Your session has expired. Please log in again.');
      case 403:
        return ForbiddenException(message ?? 'You do not have permission to do that.');
      case 404:
        return NotFoundException(message ?? 'The requested item could not be found.');
      case 402:
        return LimitReachedException(message ?? 'This action is not available on your current plan.');
      case 422:
        final rawErrors = (body is Map && body['errors'] is Map) ? body['errors'] as Map : const {};
        final errors = <String, List<String>>{
          for (final entry in rawErrors.entries) entry.key.toString(): List<String>.from(entry.value as List),
        };
        return ValidationException(message ?? 'Please check the information you entered.', errors);
      default:
        if (status != null && status >= 500) {
          return const ServerException();
        }
        return UnknownApiException(message ?? 'An unexpected error occurred.');
    }
  }
}
