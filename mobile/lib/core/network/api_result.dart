import 'api_exception.dart';

/// A tiny local Result type instead of pulling in a functional-programming
/// package — every repository method returns one of these so the UI layer
/// never has to catch exceptions directly (Phase 25 §11).
sealed class ApiResult<T> {
  const ApiResult();

  R when<R>({
    required R Function(T data) success,
    required R Function(ApiException error) failure,
  }) {
    final self = this;
    if (self is ApiSuccess<T>) return success(self.data);
    if (self is ApiFailure<T>) return failure(self.error);
    throw StateError('Unreachable');
  }
}

class ApiSuccess<T> extends ApiResult<T> {
  const ApiSuccess(this.data);

  final T data;
}

class ApiFailure<T> extends ApiResult<T> {
  const ApiFailure(this.error);

  final ApiException error;
}
