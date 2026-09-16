import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/network/cached.dart';
import '../../../../core/providers/core_providers.dart';
import '../../../../shared/models/attendance_record.dart';
import '../../../../shared/models/paginated.dart';
import '../../data/attendance_repository.dart';

final attendanceRepositoryProvider =
    Provider((ref) => AttendanceRepository(ref.watch(apiClientProvider), ref.watch(memberCachedFetchProvider)));

final attendanceHistoryProvider = FutureProvider.autoDispose<Cached<Paginated<AttendanceRecord>>>((ref) async {
  final result = await ref.watch(attendanceRepositoryProvider).history();
  return result.when(success: (data) => data, failure: (error) => throw error);
});

final attendanceSummaryProvider = FutureProvider.autoDispose<Cached<AttendanceSummary>>((ref) async {
  final result = await ref.watch(attendanceRepositoryProvider).summary();
  return result.when(success: (data) => data, failure: (error) => throw error);
});
