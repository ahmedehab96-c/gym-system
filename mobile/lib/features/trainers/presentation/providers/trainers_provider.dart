import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/providers/core_providers.dart';
import '../../../../shared/models/paginated.dart';
import '../../../../shared/models/trainer.dart';
import '../../data/trainers_repository.dart';

final trainersRepositoryProvider = Provider((ref) => TrainersRepository(ref.watch(apiClientProvider)));

final trainersListProvider = FutureProvider.autoDispose<Paginated<Trainer>>((ref) async {
  final result = await ref.watch(trainersRepositoryProvider).list();
  return result.when(success: (data) => data, failure: (error) => throw error);
});

final trainerDetailProvider = FutureProvider.autoDispose.family<Trainer, int>((ref, id) async {
  final result = await ref.watch(trainersRepositoryProvider).show(id);
  return result.when(success: (data) => data, failure: (error) => throw error);
});
