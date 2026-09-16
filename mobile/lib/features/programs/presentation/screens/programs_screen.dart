import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../../core/network/cached.dart';
import '../../../../core/widgets/async_value_view.dart';
import '../../../../core/widgets/cached_notice.dart';
import '../../../../shared/models/paginated.dart';
import '../../../../shared/models/training_program.dart';
import '../providers/programs_provider.dart';

class ProgramsScreen extends ConsumerWidget {
  const ProgramsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final programs = ref.watch(programsListProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Training Programs')),
      body: AsyncValueView<Cached<Paginated<TrainingProgram>>>(
        value: programs,
        onRetry: () => ref.invalidate(programsListProvider),
        isEmpty: (cached) => cached.data.items.isEmpty,
        emptyMessage: 'No training programs available right now.',
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
                      subtitle: Text('${program.difficulty ?? ''} · ${program.trainerName ?? ''}'),
                      trailing: const Icon(Icons.chevron_right_rounded),
                      onTap: () => context.push('/programs/${program.id}'),
                    ),
                  ),
                )),
          ],
        ),
      ),
    );
  }
}
