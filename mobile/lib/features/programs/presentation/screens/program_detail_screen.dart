import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/network/cached.dart';
import '../../../../core/widgets/async_value_view.dart';
import '../../../../core/widgets/cached_notice.dart';
import '../../../../shared/models/training_program.dart';
import '../providers/programs_provider.dart';

class ProgramDetailScreen extends ConsumerWidget {
  const ProgramDetailScreen({super.key, required this.programId});

  final int programId;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final program = ref.watch(programDetailProvider(programId));

    return Scaffold(
      appBar: AppBar(title: const Text('Program')),
      body: AsyncValueView<Cached<TrainingProgram>>(
        value: program,
        onRetry: () => ref.invalidate(programDetailProvider(programId)),
        data: (cached) {
          final p = cached.data;
          return ListView(
          padding: const EdgeInsets.all(20),
          children: [
            if (cached.meta.isFromCache) CachedDataNotice(cachedAt: cached.meta.cachedAt),
            Text(p.name, style: const TextStyle(fontSize: 22, fontWeight: FontWeight.w800)),
            const SizedBox(height: 6),
            Text('${p.difficulty ?? ''} · ${p.duration ?? ''}', style: TextStyle(color: Theme.of(context).colorScheme.onSurfaceVariant)),
            const SizedBox(height: 20),
            if (p.description != null) Text(p.description!),
            const SizedBox(height: 20),
            if (p.trainerName != null) Text('Trainer: ${p.trainerName}', style: const TextStyle(fontWeight: FontWeight.w600)),
          ],
          );
        },
      ),
    );
  }
}
