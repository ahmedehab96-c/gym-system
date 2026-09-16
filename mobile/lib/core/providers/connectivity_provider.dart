import 'dart:async';

import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../network/connectivity_service.dart';
import '../sync/sync_manager.dart';

/// Phase 27 §2 — the three states the app's offline indicator and sync
/// logic key off of. `reconnecting` is deliberately transient: it only
/// holds while a just-regained network interface is being confirmed by
/// an actual sync attempt, never indefinitely.
enum ConnectionStatus { online, offline, reconnecting }

final connectivityServiceProvider = Provider<ConnectivityService>((ref) => ConnectivityService());

final connectionStatusProvider = StateNotifierProvider<ConnectionController, ConnectionStatus>((ref) {
  final controller = ConnectionController(ref);
  ref.onDispose(controller.disposeController);
  return controller;
});

class ConnectionController extends StateNotifier<ConnectionStatus> {
  ConnectionController(this._ref) : super(ConnectionStatus.online) {
    _init();
  }

  final Ref _ref;
  StreamSubscription<bool>? _subscription;

  Future<void> _init() async {
    final service = _ref.read(connectivityServiceProvider);
    final hasConnection = await service.hasConnectionNow();
    state = hasConnection ? ConnectionStatus.online : ConnectionStatus.offline;

    _subscription = service.onStatusChange.listen(_onConnectivityChanged);
  }

  Future<void> _onConnectivityChanged(bool hasConnection) async {
    if (!hasConnection) {
      state = ConnectionStatus.offline;
      return;
    }

    if (state == ConnectionStatus.offline) {
      state = ConnectionStatus.reconnecting;
      // Best-effort: syncNow() itself never throws (SyncManager swallows
      // and retries later), so this always resolves to `online`.
      await _ref.read(syncManagerProvider).syncNow();
      state = ConnectionStatus.online;
    }
  }

  void disposeController() {
    _subscription?.cancel();
  }
}
