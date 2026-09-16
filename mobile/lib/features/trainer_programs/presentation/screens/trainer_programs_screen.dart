import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../../core/network/cached.dart';
import '../../../../core/sync/sync_manager.dart';
import '../../../../core/widgets/async_value_view.dart';
import '../../../../core/widgets/cached_notice.dart';
import '../../../../shared/models/paginated.dart';
import '../../../../shared/models/training_program.dart';
import '../providers/trainer_programs_provider.dart';

class TrainerProgramsScreen extends ConsumerWidget {
  const TrainerProgramsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final programs = ref.watch(trainerAssignedProgramsProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('My Programs')),
      body: RefreshIndicator(
        onRefresh: () => ref.read(syncManagerProvider).syncNow(),
        child: AsyncValueView<Cached<Paginated<TrainingProgram>>>(
          value: programs,
          onRetry: () => ref.invalidate(trainerAssignedProgramsProvider),
          isEmpty: (cached) => cached.data.items.isEmpty,
          emptyMessage: 'No training programs are assigned to you yet.',
          emptyIcon: Icons.local_fire_department_outlined,
          data: (cached) => ListView(
            padding: const EdgeInsets.all(16),
            children: [
              if (cached.meta.isFromCache) CachedDataNotice(cachedAt: cached.meta.cachedAt),
              ...cached.data.items.map((program) => Padding(
                    padding: const EdgeInsets.only(bottom: 10),
                    child: Card(
                      child: ListTile(
                        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                        title: Text(program.name, style: const TextStyle(fontWeight: FontWeight.w700)),
                        subtitle: Text('${program.difficulty ?? ''} · ${program.membersEnrolled ?? 0} enrolled'),
                        trailing: const Icon(Icons.chevron_right_rounded),
                        onTap: () => context.push('/trainer/programs/${program.id}'),
                      ),
                    ),
                  )),
            ],
          ),
        ),
      ),
    );
  }
}
