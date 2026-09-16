import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../../core/network/cached.dart';
import '../../../../core/widgets/async_value_view.dart';
import '../../../../core/widgets/cached_notice.dart';
import '../../../../shared/models/training_program.dart';
import '../providers/programs_provider.dart';

class MyProgramsScreen extends ConsumerWidget {
  const MyProgramsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final mine = ref.watch(myProgramsProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text('My Programs'),
        actions: [TextButton(onPressed: () => context.push('/programs'), child: const Text('Browse All'))],
      ),
      body: RefreshIndicator(
        onRefresh: () async => ref.invalidate(myProgramsProvider),
        child: AsyncValueView<Cached<List<TrainingProgram>>>(
          value: mine,
          onRetry: () => ref.invalidate(myProgramsProvider),
          isEmpty: (cached) => cached.data.isEmpty,
          emptyMessage: "You're not enrolled in any program yet.",
          emptyIcon: Icons.local_fire_department_outlined,
          data: (cached) => ListView(
            padding: const EdgeInsets.all(16),
            children: [
              if (cached.meta.isFromCache) CachedDataNotice(cachedAt: cached.meta.cachedAt),
              ...cached.data.map((program) => Padding(
                    padding: const EdgeInsets.only(bottom: 10),
                    child: _ProgramCard(program: program),
                  )),
            ],
          ),
        ),
      ),
    );
  }
}

class _ProgramCard extends StatelessWidget {
  const _ProgramCard({required this.program});

  final TrainingProgram program;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: ListTile(
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
        title: Text(program.name, style: const TextStyle(fontWeight: FontWeight.w700)),
        subtitle: Text('${program.difficulty ?? ''} · ${program.duration ?? ''}'),
        trailing: const Icon(Icons.chevron_right_rounded),
        onTap: () => context.push('/programs/${program.id}'),
      ),
    );
  }
}
