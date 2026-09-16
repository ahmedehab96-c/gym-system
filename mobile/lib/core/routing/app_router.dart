import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../features/attendance/presentation/screens/attendance_screen.dart';
import '../../features/auth/presentation/providers/auth_provider.dart';
import '../../features/auth/presentation/screens/login_screen.dart';
import '../../features/auth/presentation/screens/splash_screen.dart';
import '../../features/classes/presentation/screens/class_detail_screen.dart';
import '../../features/classes/presentation/screens/my_bookings_screen.dart';
import '../../features/dashboard/presentation/screens/main_shell.dart';
import '../../features/membership/presentation/screens/membership_history_screen.dart';
import '../../features/membership/presentation/screens/membership_screen.dart';
import '../../features/payments/presentation/screens/invoice_detail_screen.dart';
import '../../features/payments/presentation/screens/payments_screen.dart';
import '../../features/profile/presentation/screens/edit_profile_screen.dart';
import '../../features/programs/presentation/screens/my_programs_screen.dart';
import '../../features/qr/presentation/screens/my_qr_screen.dart';
import '../../features/programs/presentation/screens/program_detail_screen.dart';
import '../../features/programs/presentation/screens/programs_screen.dart';
import '../../features/trainer_attendance/presentation/screens/trainer_attendance_screen.dart';
import '../../features/trainer_auth/presentation/providers/trainer_auth_provider.dart';
import '../../features/trainer_auth/presentation/screens/trainer_login_screen.dart';
import '../../features/trainer_classes/presentation/screens/trainer_class_detail_screen.dart';
import '../../features/trainer_classes/presentation/screens/trainer_classes_screen.dart';
import '../../features/trainer_dashboard/presentation/screens/trainer_main_shell.dart';
import '../../features/trainer_members/presentation/screens/trainer_member_detail_screen.dart';
import '../../features/trainer_members/presentation/screens/trainer_members_screen.dart';
import '../../features/trainer_notifications/presentation/screens/trainer_notifications_screen.dart';
import '../../features/trainer_profile/presentation/screens/edit_trainer_profile_screen.dart';
import '../../features/trainer_programs/presentation/screens/trainer_program_detail_screen.dart';
import '../../features/trainer_programs/presentation/screens/trainer_programs_screen.dart';
import '../../features/trainers/presentation/screens/trainer_detail_screen.dart';
import '../../features/trainers/presentation/screens/trainers_screen.dart';
import '../../features/welcome/presentation/screens/welcome_screen.dart';

/// One GoRouter, rebuilt whenever either auth state changes — its
/// `redirect` is the single place session/role gating happens, for
/// BOTH actor types (Phase 26 adds Trainer alongside the existing
/// Member auth without changing how the Member flow itself works).
final routerProvider = Provider<GoRouter>((ref) {
  final memberAuth = ref.watch(authControllerProvider);
  final trainerAuth = ref.watch(trainerAuthControllerProvider);

  return GoRouter(
    initialLocation: '/',
    redirect: (context, state) {
      final path = state.matchedLocation;
      final onMemberLogin = path == '/login';
      final onTrainerLogin = path == '/trainer/login';
      final onWelcome = path == '/welcome';

      if (memberAuth is AuthChecking || trainerAuth is TrainerAuthChecking) {
        return null; // '/' renders a splash below while either is still settling
      }

      if (memberAuth is AuthAuthenticated) {
        return onMemberLogin || onWelcome ? '/' : null;
      }

      if (trainerAuth is TrainerAuthAuthenticated) {
        return onTrainerLogin || onWelcome ? '/' : null;
      }

      // Neither actor is logged in.
      if (onMemberLogin || onTrainerLogin || onWelcome) {
        return null;
      }

      return '/welcome';
    },
    routes: [
      GoRoute(path: '/welcome', builder: (context, state) => const WelcomeScreen()),
      GoRoute(path: '/login', builder: (context, state) => const LoginScreen()),
      GoRoute(path: '/trainer/login', builder: (context, state) => const TrainerLoginScreen()),
      GoRoute(
        path: '/',
        builder: (context, state) {
          if (memberAuth is AuthChecking || trainerAuth is TrainerAuthChecking) return const SplashScreen();
          if (trainerAuth is TrainerAuthAuthenticated) return const TrainerMainShell();
          return const MainShell();
        },
      ),

      // Member app routes (Phase 25) — unchanged.
      GoRoute(path: '/membership', builder: (context, state) => const MembershipScreen()),
      GoRoute(path: '/membership/history', builder: (context, state) => const MembershipHistoryScreen()),
      GoRoute(path: '/attendance', builder: (context, state) => const AttendanceScreen()),
      GoRoute(
        path: '/classes/:id',
        builder: (context, state) => ClassDetailScreen(classId: int.parse(state.pathParameters['id']!)),
      ),
      GoRoute(path: '/classes-my-bookings', builder: (context, state) => const MyBookingsScreen()),
      GoRoute(path: '/trainers', builder: (context, state) => const TrainersScreen()),
      GoRoute(
        path: '/trainers/:id',
        builder: (context, state) => TrainerDetailScreen(trainerId: int.parse(state.pathParameters['id']!)),
      ),
      GoRoute(path: '/programs', builder: (context, state) => const ProgramsScreen()),
      GoRoute(
        path: '/programs/:id',
        builder: (context, state) => ProgramDetailScreen(programId: int.parse(state.pathParameters['id']!)),
      ),
      GoRoute(path: '/programs-mine', builder: (context, state) => const MyProgramsScreen()),
      GoRoute(path: '/payments', builder: (context, state) => const PaymentsScreen()),
      GoRoute(
        path: '/invoices/:id',
        builder: (context, state) => InvoiceDetailScreen(invoiceId: int.parse(state.pathParameters['id']!)),
      ),
      GoRoute(path: '/profile/edit', builder: (context, state) => const EditProfileScreen()),
      GoRoute(path: '/qr', builder: (context, state) => const MyQrScreen()),

      // Trainer app routes (Phase 26).
      GoRoute(path: '/trainer/classes', builder: (context, state) => const TrainerClassesScreen()),
      GoRoute(
        path: '/trainer/classes/:id',
        builder: (context, state) => TrainerClassDetailScreen(classId: int.parse(state.pathParameters['id']!)),
      ),
      GoRoute(path: '/trainer/members', builder: (context, state) => const TrainerMembersScreen()),
      GoRoute(
        path: '/trainer/members/:id',
        builder: (context, state) => TrainerMemberDetailScreen(memberId: int.parse(state.pathParameters['id']!)),
      ),
      GoRoute(path: '/trainer/attendance', builder: (context, state) => const TrainerAttendanceScreen()),
      GoRoute(path: '/trainer/programs', builder: (context, state) => const TrainerProgramsScreen()),
      GoRoute(
        path: '/trainer/programs/:id',
        builder: (context, state) => TrainerProgramDetailScreen(programId: int.parse(state.pathParameters['id']!)),
      ),
      GoRoute(path: '/trainer/notifications', builder: (context, state) => const TrainerNotificationsScreen()),
      GoRoute(path: '/trainer/profile/edit', builder: (context, state) => const EditTrainerProfileScreen()),
    ],
  );
});
