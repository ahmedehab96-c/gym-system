import '../storage/cache_store.dart';
import 'offline_action.dart';

/// Persisted FIFO queue of pending offline mutations, one per actor
/// namespace (`member` / `trainer`) — backed by the same CacheStore used
/// for read caching, under a reserved key that read caching never uses
/// (Phase 27 §5).
class OfflineActionQueue {
  OfflineActionQueue(this._cacheStore, this._namespace);

  final CacheStore _cacheStore;
  final String _namespace;

  static const _queueKey = '_offline_action_queue';

  Future<List<OfflineAction>> all() async {
    final entry = await _cacheStore.read(_namespace, _queueKey);
    if (entry == null) return const [];
    final raw = entry.json['actions'] as List? ?? const [];
    return raw.map((e) => OfflineAction.fromJson(e as Map<String, dynamic>)).toList();
  }

  Future<void> enqueue(OfflineAction action) async {
    final current = await all();
    await _saveAll([...current, action]);
  }

  Future<void> remove(String id) async {
    final current = await all();
    await _saveAll(current.where((a) => a.id != id).toList());
  }

  Future<void> update(OfflineAction action) async {
    final current = await all();
    await _saveAll([for (final a in current) a.id == action.id ? action : a]);
  }

  Future<void> _saveAll(List<OfflineAction> actions) {
    return _cacheStore.write(_namespace, _queueKey, {'actions': actions.map((a) => a.toJson()).toList()});
  }
}
