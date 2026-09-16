import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/network/cached.dart';
import '../../../../core/providers/core_providers.dart';
import '../../../../shared/models/gym_class.dart';
import '../../../../shared/models/paginated.dart';
import '../../../trainer_profile/presentation/providers/trainer_profile_provider.dart';
import '../../data/schedule_models.dart';
import '../../data/trainer_classes_repository.dart';

final trainerClassesRepositoryProvider =
    Provider((ref) => TrainerClassesRepository(ref.watch(apiClientProvider), ref.watch(trainerCachedFetchProvider)));

final trainerMyClassesProvider = FutureProvider.autoDispose<Cached<Paginated<GymClass>>>((ref) async {
  final trainer = await ref.watch(currentTrainerProfileProvider.future);
  final result = await ref.watch(trainerClassesRepositoryProvider).myClasses(trainer.id);
  return result.when(success: (data) => data, failure: (error) => throw error);
});

/// Phase 29 §6 — kept separate from `trainerMyClassesProvider` for the
/// same reason as the member Classes screen's search provider: leaves
/// the unfiltered provider (and SyncManager's invalidation of it)
/// untouched.
final trainerMyClassesSearchProvider = FutureProvider.autoDispose.family<Cached<Paginated<GymClass>>, String>((ref, query) async {
  final trainer = await ref.watch(currentTrainerProfileProvider.future);
  final result = await ref.watch(trainerClassesRepositoryProvider).myClasses(trainer.id, search: query);
  return result.when(success: (data) => data, failure: (error) => throw error);
});

final trainerClassDetailProvider = FutureProvider.autoDispose.family<Cached<GymClass>, int>((ref, classId) async {
  final result = await ref.watch(trainerClassesRepositoryProvider).show(classId);
  return result.when(success: (data) => data, failure: (error) => throw error);
});

final trainerDailyScheduleProvider = FutureProvider.autoDispose<Cached<ScheduleDay>>((ref) async {
  final trainer = await ref.watch(currentTrainerProfileProvider.future);
  final result = await ref.watch(trainerClassesRepositoryProvider).daily(trainer.id);
  return result.when(success: (data) => data, failure: (error) => throw error);
});

final trainerWeeklyScheduleProvider = FutureProvider.autoDispose<Cached<WeeklySchedule>>((ref) async {
  final trainer = await ref.watch(currentTrainerProfileProvider.future);
  final result = await ref.watch(trainerClassesRepositoryProvider).weekly(trainer.id);
  return result.when(success: (data) => data, failure: (error) => throw error);
});

final trainerMonthlyScheduleProvider = FutureProvider.autoDispose<Cached<MonthlySchedule>>((ref) async {
  final trainer = await ref.watch(currentTrainerProfileProvider.future);
  final result = await ref.watch(trainerClassesRepositoryProvider).monthly(trainer.id);
  return result.when(success: (data) => data, failure: (error) => throw error);
});
