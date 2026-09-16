import 'package:flutter_test/flutter_test.dart';
import 'package:gym_member_app/core/network/api_exception.dart';
import 'package:gym_member_app/core/network/api_result.dart';
import 'package:gym_member_app/core/network/cached.dart';
import 'package:gym_member_app/features/trainer_auth/data/trainer_auth_repository.dart';
import 'package:gym_member_app/features/trainer_auth/presentation/providers/trainer_auth_provider.dart';
import 'package:gym_member_app/shared/models/staff_user.dart';
import 'package:mocktail/mocktail.dart';

class MockTrainerAuthRepository extends Mock implements TrainerAuthRepository {}

StaffUser _staffUser({String role = 'Trainer'}) => StaffUser.fromJson({
      'id': 9, 'name': 'Coach Karim', 'email': 'karim@example.com', 'role': role, 'status': 'Active', 'permissions': [],
    });

void main() {
  late MockTrainerAuthRepository repository;
  late TrainerAuthController controller;

  setUp(() {
    repository = MockTrainerAuthRepository();
    controller = TrainerAuthController(repository);
  });

  group('restoreSession', () {
    test('goes straight to Unauthenticated when no trainer session is stored', () async {
      when(() => repository.hasStoredTrainerSession()).thenAnswer((_) async => false);

      await controller.restoreSession();

      expect(controller.state, isA<TrainerAuthUnauthenticated>());
    });

    test('becomes Authenticated when a stored trainer token still validates', () async {
      when(() => repository.hasStoredTrainerSession()).thenAnswer((_) async => true);
      when(() => repository.currentUser()).thenAnswer((_) async => ApiSuccess(Cached(_staffUser(), const CacheMeta.live())));

      await controller.restoreSession();

      expect(controller.state, isA<TrainerAuthAuthenticated>());
      expect((controller.state as TrainerAuthAuthenticated).user.role, 'Trainer');
    });

    test('a network failure with nothing cached leaves the stored token alone (Phase 27)', () async {
      when(() => repository.hasStoredTrainerSession()).thenAnswer((_) async => true);
      when(() => repository.currentUser()).thenAnswer((_) async => const ApiFailure(NetworkException()));

      await controller.restoreSession();

      expect(controller.state, isA<TrainerAuthUnauthenticated>());
      verifyNever(() => repository.clearLocalSession());
    });

    test('falls back to Unauthenticated when the stored token no longer validates', () async {
      when(() => repository.hasStoredTrainerSession()).thenAnswer((_) async => true);
      when(() => repository.currentUser()).thenAnswer((_) async => const ApiFailure(UnauthorizedException()));
      when(() => repository.clearLocalSession()).thenAnswer((_) async {});

      await controller.restoreSession();

      expect(controller.state, isA<TrainerAuthUnauthenticated>());
    });
  });

  group('login', () {
    test('transitions to Authenticated on success', () async {
      when(() => repository.login(email: any(named: 'email'), password: any(named: 'password')))
          .thenAnswer((_) async => ApiSuccess(_staffUser()));

      final error = await controller.login(email: 'karim@example.com', password: 'secret');

      expect(error, isNull);
      expect(controller.state, isA<TrainerAuthAuthenticated>());
    });

    test('stays Unauthenticated and surfaces the backend error message on failure', () async {
      when(() => repository.login(email: any(named: 'email'), password: any(named: 'password')))
          .thenAnswer((_) async => const ApiFailure(ValidationException('These credentials do not match our records.', {})));

      final error = await controller.login(email: 'karim@example.com', password: 'wrong');

      expect(error, 'These credentials do not match our records.');
      expect(controller.state, isNot(isA<TrainerAuthAuthenticated>()));
    });
  });

  test('forceLogout clears an authenticated session (used when the ApiClient sees a 401)', () async {
    when(() => repository.login(email: any(named: 'email'), password: any(named: 'password')))
        .thenAnswer((_) async => ApiSuccess(_staffUser()));
    when(() => repository.clearLocalSession()).thenAnswer((_) async {});
    await controller.login(email: 'karim@example.com', password: 'secret');

    controller.forceLogout();

    expect(controller.state, isA<TrainerAuthUnauthenticated>());
    verify(() => repository.clearLocalSession()).called(1);
  });
}
