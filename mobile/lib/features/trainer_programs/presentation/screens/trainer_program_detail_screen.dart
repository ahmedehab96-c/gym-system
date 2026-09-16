import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/network/cached.dart';
import '../../../../core/widgets/app_avatar.dart';
import '../../../../core/widgets/async_value_view.dart';
import '../../../../core/widgets/cached_notice.dart';
import '../../../../shared/models/training_program.dart';
import '../providers/trainer_programs_provider.dart';

class TrainerProgramDetailScreen extends ConsumerWidget {
  const TrainerProgramDetailScreen({super.key, required this.programId});

  final int programId;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final program = ref.watch(trainerProgramDetailProvider(programId));

    return Scaffold(
      appBar: AppBar(title: const Text('Program Details')),
      body: AsyncValueView<Cached<TrainingProgram>>(
        value: program,
        onRetry: () => ref.invalidate(trainerProgramDetailProvider(programId)),
        data: (cached) {
          final p = cached.data;
          final scheme = Theme.of(context).colorScheme;
          final enrolled = p.enrolledMembers ?? const [];

          return ListView(
            padding: const EdgeInsets.all(20),
            children: [
              if (cached.meta.isFromCache) CachedDataNotice(cachedAt: cached.meta.cachedAt),
              Text(p.name, style: const TextStyle(fontSize: 22, fontWeight: FontWeight.w800)),
              const SizedBox(height: 6),
              Text('${p.difficulty ?? ''} · ${p.duration ?? ''}', style: TextStyle(color: scheme.onSurfaceVariant)),
              const SizedBox(height: 20),
              if (p.description != null) Text(p.description!),
              const SizedBox(height: 24),
              Text('Enrolled Members (${enrolled.length})', style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 16)),
              const SizedBox(height: 10),
              if (enrolled.isEmpty)
                Text('No members enrolled yet.', style: TextStyle(color: scheme.onSurfaceVariant))
              else
                ...enrolled.map((member) => Card(
                      margin: const EdgeInsets.only(bottom: 8),
                      child: ListTile(
                        leading: AppAvatar(imageUrl: member.avatar, name: member.name, radius: 18),
                        title: Text(member.name),
                      ),
                    )),
            ],
          );
        },
      ),
    );
  }
}
