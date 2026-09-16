import 'package:flutter_test/flutter_test.dart';
import 'package:gym_member_app/core/network/api_client.dart';
import 'package:gym_member_app/core/network/api_exception.dart';
import 'package:gym_member_app/features/qr/data/qr_repository.dart';
import 'package:mocktail/mocktail.dart';

class MockApiClient extends Mock implements ApiClient {}

void main() {
  late MockApiClient apiClient;
  late QrRepository repository;

  setUp(() {
    apiClient = MockApiClient();
    repository = QrRepository(apiClient);
  });

  test('show() parses the current QR token and membership status', () async {
    when(() => apiClient.get('/member/qr')).thenAnswer((_) async => {
          'data': {
            'token': 'raw-token-abc',
            'issuedAt': '2026-09-01T00:00:00Z',
            'expiresAt': '2027-03-01T00:00:00Z',
            'memberStatus': 'Active',
            'membershipExpiryDate': '2027-01-01',
          },
        });

    final result = await repository.show();

    result.when(
      success: (qr) {
        expect(qr.token, 'raw-token-abc');
        expect(qr.membershipUsable, isTrue);
        expect(qr.isExpired, isFalse);
      },
      failure: (_) => fail('expected success'),
    );
  });

  test('show() surfaces a network failure without throwing', () async {
    when(() => apiClient.get('/member/qr')).thenThrow(const NetworkException());

    final result = await repository.show();

    result.when(
      success: (_) => fail('expected failure'),
      failure: (error) => expect(error, isA<NetworkException>()),
    );
  });

  test('regenerate() POSTs and returns the new token', () async {
    when(() => apiClient.post('/member/qr/regenerate')).thenAnswer((_) async => {
          'data': {
            'token': 'new-token-xyz',
            'issuedAt': '2026-09-15T00:00:00Z',
            'expiresAt': '2027-03-15T00:00:00Z',
            'memberStatus': 'Active',
          },
        });

    final result = await repository.regenerate();

    result.when(
      success: (qr) => expect(qr.token, 'new-token-xyz'),
      failure: (_) => fail('expected success'),
    );
  });

  test('an expired token is reported via isExpired', () async {
    when(() => apiClient.get('/member/qr')).thenAnswer((_) async => {
          'data': {
            'token': 'stale-token',
            'expiresAt': '2020-01-01T00:00:00Z',
            'memberStatus': 'Active',
          },
        });

    final result = await repository.show();

    result.when(
      success: (qr) => expect(qr.isExpired, isTrue),
      failure: (_) => fail('expected success'),
    );
  });

  test('a suspended member is reported as not usable for check-in', () async {
    when(() => apiClient.get('/member/qr')).thenAnswer((_) async => {
          'data': {'token': 't', 'memberStatus': 'Suspended'},
        });

    final result = await repository.show();

    result.when(
      success: (qr) => expect(qr.membershipUsable, isFalse),
      failure: (_) => fail('expected success'),
    );
  });
}
