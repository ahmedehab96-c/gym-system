import 'package:connectivity_plus/connectivity_plus.dart';

/// Thin wrapper around connectivity_plus — the only place in the app that
/// imports it (Phase 27 §2 "centralized network connectivity detection").
/// Reports whether a network interface is up (wifi/cellular/ethernet);
/// it does not guarantee real internet reachability, which is why
/// ConnectionController (core/providers/connectivity_provider.dart) still
/// treats a reconnect as "reconnecting" until an actual API call succeeds.
class ConnectivityService {
  ConnectivityService({Connectivity? connectivity}) : _connectivity = connectivity ?? Connectivity();

  final Connectivity _connectivity;

  bool _hasConnection(List<ConnectivityResult> results) => results.any((r) => r != ConnectivityResult.none);

  Future<bool> hasConnectionNow() async => _hasConnection(await _connectivity.checkConnectivity());

  Stream<bool> get onStatusChange => _connectivity.onConnectivityChanged.map(_hasConnection);
}
