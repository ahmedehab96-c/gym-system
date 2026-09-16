import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/providers/core_providers.dart';
import '../../../../shared/models/attendance_record.dart';
import '../../data/trainer_attendance_repository.dart';

final trainerAttendanceRepositoryProvider = Provider((ref) => TrainerAttendanceRepository(ref.watch(apiClientProvider)));

final trainerTodaysAttendanceProvider = FutureProvider.autoDispose<List<AttendanceRecord>>((ref) async {
  final result = await ref.watch(trainerAttendanceRepositoryProvider).today();
  return result.when(success: (data) => data, failure: (error) => throw error);
});

class TrainerAttendanceActionController extends StateNotifier<AsyncValue<void>> {
  TrainerAttendanceActionController(this._ref) : super(const AsyncData(null));

  final Ref _ref;

  Future<String?> checkIn(int memberId) async {
    state = const AsyncLoading();
    final result = await _ref.read(trainerAttendanceRepositoryProvider).checkIn(memberId);
    return result.when(
      success: (_) {
        state = const AsyncData(null);
        _ref.invalidate(trainerTodaysAttendanceProvider);
        return null;
      },
      failure: (error) {
        state = const AsyncData(null);
        return error.message;
      },
    );
  }

  Future<String?> checkOut(int attendanceId) async {
    state = const AsyncLoading();
    final result = await _ref.read(trainerAttendanceRepositoryProvider).checkOut(attendanceId);
    return result.when(
      success: (_) {
        state = const AsyncData(null);
        _ref.invalidate(trainerTodaysAttendanceProvider);
        return null;
      },
      failure: (error) {
        state = const AsyncData(null);
        return error.message;
      },
    );
  }
}

final trainerAttendanceActionControllerProvider =
    StateNotifierProvider.autoDispose<TrainerAttendanceActionController, AsyncValue<void>>((ref) => TrainerAttendanceActionController(ref));
