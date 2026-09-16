import 'dart:async';

import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:gym_member_app/core/network/connectivity_service.dart';
import 'package:gym_member_app/core/providers/connectivity_provider.dart';
import 'package:gym_member_app/core/sync/sync_manager.dart';
import 'package:mocktail/mocktail.dart';

class MockConnectivityService extends Mock implements ConnectivityService {}

void main() {
  late MockConnectivityService service;
  late StreamController<bool> statusController;
  late int syncCallCount;
  late ProviderContainer container;

  setUp(() {
    service = MockConnectivityService();
    statusController = StreamController<bool>.broadcast();
    syncCallCount = 0;
    when(() => service.onStatusChange).thenAnswer((_) => statusController.stream);

    container = ProviderContainer(overrides: [
      connectivityServiceProvider.overrideWithValue(service),
      // A fake SyncManager whose syncNow() just counts calls and resolves
      // immediately — this test is about ConnectionController's state
      // transitions, not sync's own internals (covered separately).
      syncManagerProvider.overrideWith((ref) => _CountingSyncManager(ref, () => syncCallCount++)),
    ]);
  });

  tearDown(() {
    container.dispose();
    statusController.close();
  });

  test('starts online when the device already has connectivity', () async {
    when(() => service.hasConnectionNow()).thenAnswer((_) async => true);

    // Read once to create the controller, then let _init()'s Future resolve.
    container.read(connectionStatusProvider);
    await Future<void>.delayed(Duration.zero);

    expect(container.read(connectionStatusProvider), ConnectionStatus.online);
  });

  test('starts offline when the device has no connectivity at construction', () async {
    when(() => service.hasConnectionNow()).thenAnswer((_) async => false);

    container.read(connectionStatusProvider);
    await Future<void>.delayed(Duration.zero);

    expect(container.read(connectionStatusProvider), ConnectionStatus.offline);
  });

  test('losing connectivity moves straight to offline', () async {
    when(() => service.hasConnectionNow()).thenAnswer((_) async => true);
    container.read(connectionStatusProvider);
    await Future<void>.delayed(Duration.zero);

    statusController.add(false);
    await Future<void>.delayed(Duration.zero);

    expect(container.read(connectionStatusProvider), ConnectionStatus.offline);
  });

  test('regaining connectivity after being offline triggers a sync and ends up online', () async {
    when(() => service.hasConnectionNow()).thenAnswer((_) async => false);
    container.read(connectionStatusProvider);
    await Future<void>.delayed(Duration.zero);
    expect(container.read(connectionStatusProvider), ConnectionStatus.offline);

    statusController.add(true);
    await Future<void>.delayed(Duration.zero);

    expect(container.read(connectionStatusProvider), ConnectionStatus.online);
    expect(syncCallCount, 1);
  });

  test('a connectivity event while already online does not trigger another sync', () async {
    when(() => service.hasConnectionNow()).thenAnswer((_) async => true);
    container.read(connectionStatusProvider);
    await Future<void>.delayed(Duration.zero);

    statusController.add(true);
    await Future<void>.delayed(Duration.zero);

    expect(syncCallCount, 0);
  });
}

class _CountingSyncManager extends SyncManager {
  _CountingSyncManager(super.ref, this._onSync);

  final void Function() _onSync;

  @override
  Future<void> syncNow() async {
    _onSync();
  }
}
