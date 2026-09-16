import 'package:flutter_test/flutter_test.dart';
import 'package:gym_member_app/core/network/api_exception.dart';
import 'package:gym_member_app/core/network/api_result.dart';
import 'package:gym_member_app/core/network/cached.dart';
import 'package:gym_member_app/features/auth/data/auth_repository.dart';
import 'package:gym_member_app/features/auth/presentation/providers/auth_provider.dart';
import 'package:gym_member_app/shared/models/member.dart';
import 'package:mocktail/mocktail.dart';

class MockAuthRepository extends Mock implements AuthRepository {}

Member _member({String name = 'Alex'}) => Member.fromJson({'id': 1, 'name': name, 'email': 'a@example.com', 'status': 'Active'});

void main() {
  late MockAuthRepository repository;
  late AuthController controller;

  setUp(() {
    repository = MockAuthRepository();
    controller = AuthController(repository);
  });

  group('restoreSession', () {
    test('goes straight to Unauthenticated when no token is stored', () async {
      when(() => repository.hasStoredSession()).thenAnswer((_) async => false);

      await controller.restoreSession();

      expect(controller.state, isA<AuthUnauthenticated>());
    });

    test('becomes Authenticated when a stored token still validates', () async {
      when(() => repository.hasStoredSession()).thenAnswer((_) async => true);
      when(() => repository.currentMember()).thenAnswer((_) async => ApiSuccess(Cached(_member(), const CacheMeta.live())));

      await controller.restoreSession();

      expect(controller.state, isA<AuthAuthenticated>());
      expect((controller.state as AuthAuthenticated).member.name, 'Alex');
    });

    test('becomes Authenticated from a cached profile when currentMember() served it from cache', () async {
      when(() => repository.hasStoredSession()).thenAnswer((_) async => true);
      when(() => repository.currentMember())
          .thenAnswer((_) async => ApiSuccess(Cached(_member(), CacheMeta.cached(DateTime(2026, 9, 1)))));

      await controller.restoreSession();

      expect(controller.state, isA<AuthAuthenticated>());
      expect((controller.state as AuthAuthenticated).member.name, 'Alex');
    });

    test('falls back to Unauthenticated (not stuck) when the stored token no longer validates', () async {
      when(() => repository.hasStoredSession()).thenAnswer((_) async => true);
      when(() => repository.currentMember()).thenAnswer((_) async => const ApiFailure(UnauthorizedException()));
      when(() => repository.clearLocalSession()).thenAnswer((_) async {});

      await controller.restoreSession();

      expect(controller.state, isA<AuthUnauthenticated>());
      verify(() => repository.clearLocalSession()).called(1);
    });

    test('a network failure with nothing cached leaves the stored token alone (Phase 27)', () async {
      when(() => repository.hasStoredSession()).thenAnswer((_) async => true);
      when(() => repository.currentMember()).thenAnswer((_) async => const ApiFailure(NetworkException()));

      await controller.restoreSession();

      expect(controller.state, isA<AuthUnauthenticated>());
      verifyNever(() => repository.clearLocalSession());
    });
  });

  group('login', () {
    test('transitions to Authenticated and returns no error on success', () async {
      when(() => repository.login(email: any(named: 'email'), password: any(named: 'password')))
          .thenAnswer((_) async => ApiSuccess(_member()));

      final error = await controller.login(email: 'a@example.com', password: 'secret');

      expect(error, isNull);
      expect(controller.state, isA<AuthAuthenticated>());
    });

    test('stays Unauthenticated and returns the error message on failure', () async {
      when(() => repository.login(email: any(named: 'email'), password: any(named: 'password')))
          .thenAnswer((_) async => const ApiFailure(ValidationException('Invalid credentials', {})));

      final error = await controller.login(email: 'a@example.com', password: 'wrong');

      expect(error, 'Invalid credentials');
      expect(controller.state, isNot(isA<AuthAuthenticated>()));
    });
  });

  group('forceLogout', () {
    test('only clears an Authenticated session, never re-triggers from an already-logged-out state', () async {
      when(() => repository.login(email: any(named: 'email'), password: any(named: 'password')))
          .thenAnswer((_) async => ApiSuccess(_member()));
      when(() => repository.clearLocalSession()).thenAnswer((_) async {});
      await controller.login(email: 'a@example.com', password: 'secret');

      controller.forceLogout();

      expect(controller.state, isA<AuthUnauthenticated>());
      verify(() => repository.clearLocalSession()).called(1);
    });
  });
}
