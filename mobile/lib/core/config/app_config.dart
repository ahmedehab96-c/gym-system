/// Build-time configuration — never hardcode secrets here. The only value
/// this app needs is where the Laravel API lives; there is no API key or
/// other credential compiled into the mobile binary (Phase 25 §13).
///
/// Override per environment with:
///   flutter run --dart-define=API_BASE_URL=https://api.example.com/api/v1
class AppConfig {
  const AppConfig._();

  /// Android emulator's alias for the host machine's localhost. iOS
  /// simulator and physical devices need a different value passed via
  /// --dart-define at build/run time.
  static const String apiBaseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'http://10.0.2.2:8000/api/v1',
  );

  static const Duration connectTimeout = Duration(seconds: 15);
  static const Duration receiveTimeout = Duration(seconds: 20);
}
