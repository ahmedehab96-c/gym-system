import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/network/cached.dart';
import '../../../../core/providers/core_providers.dart';
import '../../../../shared/models/attendance_record.dart';
import '../../../../shared/models/member.dart';
import '../../../../shared/models/paginated.dart';
import '../../../trainer_profile/presentation/providers/trainer_profile_provider.dart';
import '../../data/trainer_members_repository.dart';

final trainerMembersRepositoryProvider =
    Provider((ref) => TrainerMembersRepository(ref.watch(apiClientProvider), ref.watch(trainerCachedFetchProvider)));

final trainerAssignedMembersProvider = FutureProvider.autoDispose<Cached<Paginated<Member>>>((ref) async {
  final trainer = await ref.watch(currentTrainerProfileProvider.future);
  final result = await ref.watch(trainerMembersRepositoryProvider).assignedMembers(trainer.id);
  return result.when(success: (data) => data, failure: (error) => throw error);
});

final trainerAssignedMembersSearchProvider = FutureProvider.autoDispose.family<Cached<Paginated<Member>>, String>((ref, query) async {
  final trainer = await ref.watch(currentTrainerProfileProvider.future);
  final result = await ref.watch(trainerMembersRepositoryProvider).assignedMembers(trainer.id, search: query);
  return result.when(success: (data) => data, failure: (error) => throw error);
});

final trainerMemberDetailProvider = FutureProvider.autoDispose.family<Cached<Member>, int>((ref, memberId) async {
  final result = await ref.watch(trainerMembersRepositoryProvider).show(memberId);
  return result.when(success: (data) => data, failure: (error) => throw error);
});

final trainerMemberAttendanceProvider = FutureProvider.autoDispose.family<Cached<Paginated<AttendanceRecord>>, int>((ref, memberId) async {
  final result = await ref.watch(trainerMembersRepositoryProvider).memberAttendance(memberId);
  return result.when(success: (data) => data, failure: (error) => throw error);
});
