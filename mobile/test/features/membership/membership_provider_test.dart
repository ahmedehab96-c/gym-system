import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:gym_member_app/core/network/api_client.dart';
import 'package:gym_member_app/core/network/api_exception.dart';
import 'package:gym_member_app/core/providers/core_providers.dart';
import 'package:gym_member_app/core/storage/cache_store.dart';
import 'package:gym_member_app/features/membership/presentation/providers/membership_provider.dart';
import 'package:mocktail/mocktail.dart';

class MockApiClient extends Mock implements ApiClient {}

class MockCacheStore extends Mock implements CacheStore {}

void main() {
  late MockApiClient apiClient;
  late MockCacheStore cacheStore;
  late ProviderContainer container;

  setUp(() {
    apiClient = MockApiClient();
    cacheStore = MockCacheStore();
    when(() => cacheStore.write(any(), any(), any())).thenAnswer((_) async {});
    container = ProviderContainer(overrides: [
      apiClientProvider.overrideWithValue(apiClient),
      cacheStoreProvider.overrideWithValue(cacheStore),
    ]);
  });

  tearDown(() => container.dispose());

  test('currentMembershipProvider resolves to null (not an error) when the backend returns 404', () async {
    when(() => apiClient.get('/member/membership')).thenThrow(const NotFoundException('No membership found.'));

    final result = await container.read(currentMembershipProvider.future);

    expect(result, isNull);
  });

  test('currentMembershipProvider resolves to the membership on success', () async {
    when(() => apiClient.get('/member/membership')).thenAnswer((_) async => {
          'data': {
            'id': 1, 'memberId': 1, 'planName': 'Pro', 'startDate': '2026-01-01', 'expiryDate': '2026-12-31',
            'price': 79, 'status': 'Active',
          },
        });

    final result = await container.read(currentMembershipProvider.future);

    expect(result, isNotNull);
    expect(result!.data.status, 'Active');
    expect(result.meta.isFromCache, isFalse);
  });

  test('currentMembershipProvider still throws for a non-404 failure (e.g. network error) with nothing cached', () async {
    when(() => apiClient.get('/member/membership')).thenThrow(const NetworkException());
    when(() => cacheStore.read(any(), any())).thenAnswer((_) async => null);

    await expectLater(container.read(currentMembershipProvider.future), throwsA(isA<NetworkException>()));
  });
}
