import 'package:flutter_test/flutter_test.dart';
import 'package:gym_member_app/core/storage/cache_store.dart';
import 'package:gym_member_app/core/sync/offline_action.dart';
import 'package:gym_member_app/core/sync/offline_action_queue.dart';
import 'package:shared_preferences_platform_interface/in_memory_shared_preferences_async.dart';
import 'package:shared_preferences_platform_interface/shared_preferences_async_platform_interface.dart';

void main() {
  late OfflineActionQueue queue;

  setUp(() {
    SharedPreferencesAsyncPlatform.instance = InMemorySharedPreferencesAsync.empty();
    queue = OfflineActionQueue(CacheStore(), 'member');
  });

  OfflineAction action({String id = 'a1', OfflineActionKind kind = OfflineActionKind.classCancel}) => OfflineAction(
        id: id,
        kind: kind,
        payload: {'classId': 5},
        createdAt: DateTime(2026, 9, 1),
      );

  test('all() is empty before anything is queued', () async {
    expect(await queue.all(), isEmpty);
  });

  test('enqueue() persists an action that all() then returns', () async {
    await queue.enqueue(action());

    final all = await queue.all();

    expect(all, hasLength(1));
    expect(all.single.id, 'a1');
    expect(all.single.kind, OfflineActionKind.classCancel);
    expect(all.single.payload, {'classId': 5});
  });

  test('enqueue() preserves FIFO order across multiple actions', () async {
    await queue.enqueue(action(id: 'a1'));
    await queue.enqueue(action(id: 'a2', kind: OfflineActionKind.notificationMarkRead));

    final all = await queue.all();

    expect(all.map((a) => a.id), ['a1', 'a2']);
  });

  test('remove() drops only the matching action', () async {
    await queue.enqueue(action(id: 'a1'));
    await queue.enqueue(action(id: 'a2'));

    await queue.remove('a1');

    final all = await queue.all();
    expect(all.map((a) => a.id), ['a2']);
  });

  test('update() replaces an action in place (used to bump its attempt count)', () async {
    await queue.enqueue(action(id: 'a1'));

    final bumped = (await queue.all()).single.bumpAttempts();
    await queue.update(bumped);

    final all = await queue.all();
    expect(all.single.attempts, 1);
  });
}
