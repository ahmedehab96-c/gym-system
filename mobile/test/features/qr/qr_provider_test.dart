import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:gym_member_app/core/network/api_exception.dart';
import 'package:gym_member_app/core/network/api_result.dart';
import 'package:gym_member_app/features/qr/data/member_qr.dart';
import 'package:gym_member_app/features/qr/data/qr_repository.dart';
import 'package:gym_member_app/features/qr/presentation/providers/qr_provider.dart';
import 'package:mocktail/mocktail.dart';

class MockQrRepository extends Mock implements QrRepository {}

MemberQr _qr({String token = 't', String status = 'Active'}) => MemberQr(token: token, memberStatus: status);

void main() {
  late MockQrRepository repository;
  late ProviderContainer container;

  setUp(() {
    repository = MockQrRepository();
    container = ProviderContainer(overrides: [qrRepositoryProvider.overrideWithValue(repository)]);
  });

  tearDown(() => container.dispose());

  test('regenerate() invalidates myQrProvider on success', () async {
    when(() => repository.regenerate()).thenAnswer((_) async => ApiSuccess(_qr(token: 'new')));

    final error = await container.read(qrRegenerateControllerProvider.notifier).regenerate();

    expect(error, isNull);
  });

  test('regenerate() surfaces the backend error message on failure', () async {
    when(() => repository.regenerate()).thenAnswer((_) async => const ApiFailure(ServerException()));

    final error = await container.read(qrRegenerateControllerProvider.notifier).regenerate();

    expect(error, isNotNull);
  });
}
