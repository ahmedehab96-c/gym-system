import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/widgets/app_avatar.dart';
import '../../../../core/widgets/async_value_view.dart';
import '../../../../shared/models/trainer.dart';
import '../providers/trainers_provider.dart';

class TrainerDetailScreen extends ConsumerWidget {
  const TrainerDetailScreen({super.key, required this.trainerId});

  final int trainerId;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final trainer = ref.watch(trainerDetailProvider(trainerId));

    return Scaffold(
      appBar: AppBar(title: const Text('Trainer')),
      body: AsyncValueView<Trainer>(
        value: trainer,
        onRetry: () => ref.invalidate(trainerDetailProvider(trainerId)),
        data: (t) => ListView(
          padding: const EdgeInsets.all(20),
          children: [
            Center(
              child: Column(
                children: [
                  AppAvatar(imageUrl: t.photo, name: t.name, radius: 48),
                  const SizedBox(height: 12),
                  Text(t.name, style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w800)),
                  Text(t.specialty ?? '', style: TextStyle(color: Theme.of(context).colorScheme.onSurfaceVariant)),
                ],
              ),
            ),
            const SizedBox(height: 24),
            if (t.bio != null) ...[
              const Text('About', style: TextStyle(fontWeight: FontWeight.w700)),
              const SizedBox(height: 6),
              Text(t.bio!),
              const SizedBox(height: 20),
            ],
            if (t.specialties.isNotEmpty) ...[
              const Text('Specialties', style: TextStyle(fontWeight: FontWeight.w700)),
              const SizedBox(height: 8),
              Wrap(
                spacing: 8,
                runSpacing: 8,
                children: t.specialties.map((s) => Chip(label: Text(s))).toList(),
              ),
              const SizedBox(height: 20),
            ],
            if (t.experience != null) _InfoRow(label: 'Experience', value: t.experience!),
            _InfoRow(label: 'Rating', value: '${t.rating.toStringAsFixed(1)} / 5'),
          ],
        ),
      ),
    );
  }
}

class _InfoRow extends StatelessWidget {
  const _InfoRow({required this.label, required this.value});

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: TextStyle(color: Theme.of(context).colorScheme.onSurfaceVariant)),
          Text(value, style: const TextStyle(fontWeight: FontWeight.w600)),
        ],
      ),
    );
  }
}
