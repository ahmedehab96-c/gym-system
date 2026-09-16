import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/providers/core_providers.dart';
import '../../data/trainer_dashboard_repository.dart';

final trainerDashboardRepositoryProvider = Provider((ref) => TrainerDashboardRepository(ref.watch(apiClientProvider)));

final trainerDashboardProvider = FutureProvider.autoDispose((ref) async {
  final result = await ref.watch(trainerDashboardRepositoryProvider).fetch();
  return result.when(success: (data) => data, failure: (error) => throw error);
});
