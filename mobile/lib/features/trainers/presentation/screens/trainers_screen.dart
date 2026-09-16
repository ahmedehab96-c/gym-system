import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../../core/widgets/app_avatar.dart';
import '../../../../core/widgets/async_value_view.dart';
import '../../../../shared/models/paginated.dart';
import '../../../../shared/models/trainer.dart';
import '../providers/trainers_provider.dart';

class TrainersScreen extends ConsumerWidget {
  const TrainersScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final trainers = ref.watch(trainersListProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Trainers')),
      body: RefreshIndicator(
        onRefresh: () async => ref.invalidate(trainersListProvider),
        child: AsyncValueView<Paginated<Trainer>>(
          value: trainers,
          onRetry: () => ref.invalidate(trainersListProvider),
          isEmpty: (data) => data.items.isEmpty,
          emptyMessage: 'No trainers available right now.',
          emptyIcon: Icons.groups_outlined,
          data: (data) => ListView.separated(
            padding: const EdgeInsets.all(16),
            itemCount: data.items.length,
            separatorBuilder: (_, _) => const SizedBox(height: 10),
            itemBuilder: (context, i) {
              final trainer = data.items[i];
              return Card(
                child: ListTile(
                  contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                  leading: AppAvatar(imageUrl: trainer.photo, name: trainer.name),
                  title: Text(trainer.name, style: const TextStyle(fontWeight: FontWeight.w700)),
                  subtitle: Text(trainer.specialty ?? 'Trainer'),
                  trailing: const Icon(Icons.chevron_right_rounded),
                  onTap: () => context.push('/trainers/${trainer.id}'),
                ),
              );
            },
          ),
        ),
      ),
    );
  }
}
