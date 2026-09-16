import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../network/api_client.dart';
import '../network/cached_fetch.dart';
import '../storage/cache_store.dart';
import '../storage/secure_storage.dart';
import '../sync/offline_action_queue.dart';

/// The lowest-level, app-wide singletons — every feature's repository
/// provider depends on these, never constructs its own ApiClient/
/// CacheStore.
final secureStorageProvider = Provider<SecureStorage>((ref) => SecureStorage());

final apiClientProvider = Provider<ApiClient>((ref) {
  return ApiClient(secureStorage: ref.watch(secureStorageProvider));
});

/// Phase 27 — the shared local cache backing offline reads for both apps.
final cacheStoreProvider = Provider<CacheStore>((ref) => CacheStore());

final memberCachedFetchProvider = Provider<CachedFetch>((ref) => CachedFetch(ref.watch(cacheStoreProvider), 'member'));

final trainerCachedFetchProvider = Provider<CachedFetch>((ref) => CachedFetch(ref.watch(cacheStoreProvider), 'trainer'));

final memberOfflineActionQueueProvider =
    Provider<OfflineActionQueue>((ref) => OfflineActionQueue(ref.watch(cacheStoreProvider), 'member'));

final trainerOfflineActionQueueProvider =
    Provider<OfflineActionQueue>((ref) => OfflineActionQueue(ref.watch(cacheStoreProvider), 'trainer'));
