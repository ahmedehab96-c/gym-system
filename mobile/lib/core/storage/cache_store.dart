import 'dart:convert';

import 'package:shared_preferences/shared_preferences.dart';

/// A cached JSON blob plus when it was written — every read-heavy
/// repository method that supports offline reads (Phase 27 §1) writes its
/// raw API response envelope here on success and falls back to reading it
/// back when the network is unavailable.
class CacheEntry {
  const CacheEntry({required this.json, required this.cachedAt});

  final Map<String, dynamic> json;
  final DateTime cachedAt;
}

/// Thin, namespaced wrapper around SharedPreferences — the one local
/// cache used by both the Member and Trainer apps (Phase 27 §1: "use the
/// project's existing storage solution if suitable; otherwise choose one
/// lightweight production-ready local storage package"). Nothing
/// sensitive lives here: auth tokens stay in SecureStorage exactly as
/// before; this only ever holds already-authorized read responses
/// (classes, notifications, profile, etc.) for offline display.
///
/// Keys are namespaced per actor type (`member` / `trainer`) so the two
/// apps sharing one device slot (see SecureStorage's docblock) never read
/// each other's cached data, and so `clearNamespace` can wipe one actor's
/// cache cleanly on logout/login without touching the other's.
class CacheStore {
  CacheStore({SharedPreferencesAsync? prefs}) : _prefs = prefs ?? SharedPreferencesAsync();

  final SharedPreferencesAsync _prefs;

  static const _prefix = 'gym_cache:';

  String _fullKey(String namespace, String key) => '$_prefix$namespace:$key';

  Future<void> write(String namespace, String key, Map<String, dynamic> json) {
    final envelope = jsonEncode({'json': json, 'cachedAt': DateTime.now().toIso8601String()});
    return _prefs.setString(_fullKey(namespace, key), envelope);
  }

  Future<CacheEntry?> read(String namespace, String key) async {
    final raw = await _prefs.getString(_fullKey(namespace, key));
    if (raw == null) return null;
    try {
      final decoded = jsonDecode(raw) as Map<String, dynamic>;
      return CacheEntry(
        json: decoded['json'] as Map<String, dynamic>,
        cachedAt: DateTime.parse(decoded['cachedAt'] as String),
      );
    } catch (_) {
      // Corrupt/old-shape entry — treat as a cache miss rather than crash.
      return null;
    }
  }

  Future<void> remove(String namespace, String key) => _prefs.remove(_fullKey(namespace, key));

  /// Wipes every cached entry for one actor namespace — called on logout
  /// and before a fresh login completes, so a new session on the same
  /// device never briefly shows the previous member/trainer's data
  /// (Phase 27 §1/§6: "secure local caching").
  Future<void> clearNamespace(String namespace) async {
    final keys = await _prefs.getKeys();
    final ours = keys.where((k) => k.startsWith('$_prefix$namespace:'));
    for (final key in ours) {
      await _prefs.remove(key);
    }
  }
}
