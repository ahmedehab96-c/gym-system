import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/network/cached.dart';
import '../../../../core/providers/core_providers.dart';
import '../../../../shared/models/paginated.dart';
import '../../../../shared/models/training_program.dart';
import '../../data/programs_repository.dart';

final programsRepositoryProvider =
    Provider((ref) => ProgramsRepository(ref.watch(apiClientProvider), ref.watch(memberCachedFetchProvider)));

final programsListProvider = FutureProvider.autoDispose<Cached<Paginated<TrainingProgram>>>((ref) async {
  final result = await ref.watch(programsRepositoryProvider).list();
  return result.when(success: (data) => data, failure: (error) => throw error);
});

final myProgramsProvider = FutureProvider.autoDispose<Cached<List<TrainingProgram>>>((ref) async {
  final result = await ref.watch(programsRepositoryProvider).mine();
  return result.when(success: (data) => data, failure: (error) => throw error);
});

final programDetailProvider = FutureProvider.autoDispose.family<Cached<TrainingProgram>, int>((ref, id) async {
  final result = await ref.watch(programsRepositoryProvider).show(id);
  return result.when(success: (data) => data, failure: (error) => throw error);
});
