/// Every failure the API layer can produce, normalized from whatever Dio
/// threw so no repository or screen ever needs to know about Dio/HTTP
/// details directly (Phase 25 §11 — "handle loading/empty/error/network
/// failure/unauthorized/validation errors").
sealed class ApiException implements Exception {
  const ApiException(this.message);

  final String message;
}

/// No connectivity, DNS failure, timed-out socket, etc. — distinct from a
/// server-returned error so the UI can offer a "check your connection"
/// message with a retry action.
class NetworkException extends ApiException {
  const NetworkException([super.message = 'No internet connection. Please check your network and try again.']);
}

/// 401 — the session token is missing, invalid, or was revoked server-side.
/// The UI layer reacts to this by forcing a logout + redirect to login,
/// never by retrying with the same token.
class UnauthorizedException extends ApiException {
  const UnauthorizedException([super.message = 'Your session has expired. Please log in again.']);
}

/// 403 — authenticated, but the backend's own authorization rules (tenant
/// isolation, role/ownership checks) rejected the action. The backend
/// remains the sole source of truth for this; the app never second-guesses it.
class ForbiddenException extends ApiException {
  const ForbiddenException([super.message = 'You do not have permission to do that.']);
}

/// 404.
class NotFoundException extends ApiException {
  const NotFoundException([super.message = 'The requested item could not be found.']);
}

/// 422 — field-level validation errors, keyed the same way Laravel's
/// ApiResponse::error() returns them, so a form can highlight the exact
/// field that failed.
class ValidationException extends ApiException {
  const ValidationException(super.message, this.errors);

  final Map<String, List<String>> errors;

  String? firstErrorFor(String field) => errors[field]?.first;
}

/// 402 — a plan/usage limit was hit (mirrors the backend's subscription/AI
/// limit responses elsewhere in this system); surfaced distinctly in case
/// a future member-facing limit uses the same status code.
class LimitReachedException extends ApiException {
  const LimitReachedException(super.message);
}

/// 5xx or an unrecognized shape — a generic "something went wrong" the UI
/// can still show a retry action for.
class ServerException extends ApiException {
  const ServerException([super.message = 'Something went wrong on our end. Please try again shortly.']);
}

class UnknownApiException extends ApiException {
  const UnknownApiException([super.message = 'An unexpected error occurred.']);
}
