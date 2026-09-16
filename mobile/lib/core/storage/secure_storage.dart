import 'package:flutter_secure_storage/flutter_secure_storage.dart';

/// The only place an auth token touches disk — Keychain on iOS,
/// EncryptedSharedPreferences/Keystore on Android (Phase 25 §2/§13: "do
/// not store sensitive credentials insecurely"). Nothing else in the app
/// should read/write SharedPreferences or plain files for a token.
///
/// One device session at a time, for either actor type (Phase 26): a
/// Member login and a Trainer login share this same token slot — logging
/// in as one signs the other out, matching how a phone realistically
/// belongs to one person acting as one role. `actorType` records which
/// kind of session the stored token belongs to, so the app can decide at
/// startup which restoreSession() to run without guessing.
class SecureStorage {
  SecureStorage({FlutterSecureStorage? storage})
      : _storage = storage ??
            const FlutterSecureStorage(
              aOptions: AndroidOptions(encryptedSharedPreferences: true),
            );

  final FlutterSecureStorage _storage;

  static const _tokenKey = 'auth_token';
  static const _actorTypeKey = 'auth_actor_type';

  Future<void> saveToken(String token) => _storage.write(key: _tokenKey, value: token);

  Future<String?> readToken() => _storage.read(key: _tokenKey);

  Future<void> clearToken() => _storage.delete(key: _tokenKey);

  Future<void> saveActorType(String actorType) => _storage.write(key: _actorTypeKey, value: actorType);

  Future<String?> readActorType() => _storage.read(key: _actorTypeKey);

  Future<void> clearActorType() => _storage.delete(key: _actorTypeKey);
}
