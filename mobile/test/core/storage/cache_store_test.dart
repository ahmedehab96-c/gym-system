import 'package:flutter_test/flutter_test.dart';
import 'package:gym_member_app/core/storage/cache_store.dart';
import 'package:shared_preferences_platform_interface/in_memory_shared_preferences_async.dart';
import 'package:shared_preferences_platform_interface/shared_preferences_async_platform_interface.dart';

void main() {
  late CacheStore store;

  setUp(() {
    SharedPreferencesAsyncPlatform.instance = InMemorySharedPreferencesAsync.empty();
    store = CacheStore();
  });

  test('read() is a cache miss before anything is written', () async {
    expect(await store.read('member', 'classes'), isNull);
  });

  test('write() then read() round-trips the JSON and a cachedAt timestamp', () async {
    await store.write('member', 'classes', {'foo': 'bar'});

    final entry = await store.read('member', 'classes');

    expect(entry, isNotNull);
    expect(entry!.json, {'foo': 'bar'});
    expect(DateTime.now().difference(entry.cachedAt).inSeconds, lessThan(5));
  });

  test('remove() deletes a single key without touching others', () async {
    await store.write('member', 'classes', {'a': 1});
    await store.write('member', 'notifications', {'b': 2});

    await store.remove('member', 'classes');

    expect(await store.read('member', 'classes'), isNull);
    expect(await store.read('member', 'notifications'), isNotNull);
  });

  test('clearNamespace() wipes only that actor\'s entries, never the other actor\'s', () async {
    await store.write('member', 'classes', {'a': 1});
    await store.write('member', 'profile', {'a': 2});
    await store.write('trainer', 'profile', {'a': 3});

    await store.clearNamespace('member');

    expect(await store.read('member', 'classes'), isNull);
    expect(await store.read('member', 'profile'), isNull);
    expect(await store.read('trainer', 'profile'), isNotNull);
  });
}
