import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/network/cached.dart';
import '../../../../core/providers/core_providers.dart';
import '../../../../shared/models/paginated.dart';
import '../../../../shared/models/training_program.dart';
import '../../../trainer_profile/presentation/providers/trainer_profile_provider.dart';
import '../../data/trainer_programs_repository.dart';

final trainerProgramsRepositoryProvider =
    Provider((ref) => TrainerProgramsRepository(ref.watch(apiClientProvider), ref.watch(trainerCachedFetchProvider)));

final trainerAssignedProgramsProvider = FutureProvider.autoDispose<Cached<Paginated<TrainingProgram>>>((ref) async {
  final trainer = await ref.watch(currentTrainerProfileProvider.future);
  final result = await ref.watch(trainerProgramsRepositoryProvider).assignedPrograms(trainer.id);
  return result.when(success: (data) => data, failure: (error) => throw error);
});

final trainerProgramDetailProvider = FutureProvider.autoDispose.family<Cached<TrainingProgram>, int>((ref, programId) async {
  final result = await ref.watch(trainerProgramsRepositoryProvider).show(programId);
  return result.when(success: (data) => data, failure: (error) => throw error);
});
