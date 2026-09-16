import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/providers/core_providers.dart';
import '../../data/dashboard_repository.dart';

final dashboardRepositoryProvider = Provider((ref) => DashboardRepository(ref.watch(apiClientProvider)));

final dashboardProvider = FutureProvider.autoDispose((ref) async {
  final result = await ref.watch(dashboardRepositoryProvider).fetch();
  return result.when(success: (data) => data, failure: (error) => throw error);
});
