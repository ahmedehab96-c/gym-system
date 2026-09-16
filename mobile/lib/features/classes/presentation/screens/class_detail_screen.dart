import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/network/cached.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/widgets/async_value_view.dart';
import '../../../../core/widgets/cached_notice.dart';
import '../../../../core/widgets/status_badge.dart';
import '../../../../shared/models/gym_class.dart';
import '../providers/classes_provider.dart';

class ClassDetailScreen extends ConsumerWidget {
  const ClassDetailScreen({super.key, required this.classId});

  final int classId;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final detail = ref.watch(classDetailProvider(classId));
    final actionState = ref.watch(classActionControllerProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Class Details')),
      body: AsyncValueView<Cached<GymClass>>(
        value: detail,
        onRetry: () => ref.invalidate(classDetailProvider(classId)),
        data: (cached) {
          final gymClass = cached.data;
          final isBusy = actionState.isLoading;

          return Padding(
            padding: const EdgeInsets.all(20),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                if (cached.meta.isFromCache) CachedDataNotice(cachedAt: cached.meta.cachedAt),
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Expanded(child: Text(gymClass.name, style: const TextStyle(fontSize: 22, fontWeight: FontWeight.w800))),
                    StatusBadge(status: gymClass.status),
                  ],
                ),
                const SizedBox(height: 6),
                Text(gymClass.category ?? '', style: TextStyle(color: Theme.of(context).colorScheme.onSurfaceVariant)),
                const SizedBox(height: 24),
                _InfoRow(icon: Icons.person_outline_rounded, label: 'Trainer', value: gymClass.trainerName ?? 'Unassigned'),
                _InfoRow(icon: Icons.event_outlined, label: 'Date', value: Formatters.date(gymClass.date)),
                _InfoRow(icon: Icons.access_time_rounded, label: 'Time', value: '${gymClass.startTime ?? ''} - ${gymClass.endTime ?? ''}'),
                _InfoRow(icon: Icons.groups_outlined, label: 'Capacity', value: '${gymClass.booked}/${gymClass.capacity} booked'),
                const Spacer(),
                if (gymClass.status != 'Cancelled' && gymClass.status != 'Completed')
                  ElevatedButton(
                    onPressed: (isBusy || (gymClass.isFull && !gymClass.isBookedByMe))
                        ? null
                        : () async {
                            final controller = ref.read(classActionControllerProvider.notifier);
                            final error = gymClass.isBookedByMe
                                ? await controller.cancel(classId)
                                : await controller.book(classId);
                            if (!context.mounted) return;
                            if (error != null) {
                              ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(error)));
                            }
                          },
                    style: gymClass.isBookedByMe
                        ? ElevatedButton.styleFrom(backgroundColor: Theme.of(context).colorScheme.errorContainer, foregroundColor: Theme.of(context).colorScheme.onErrorContainer)
                        : null,
                    child: isBusy
                        ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2.4, color: Colors.white))
                        : Text(gymClass.isBookedByMe ? 'Cancel Booking' : (gymClass.isFull ? 'Class Full' : 'Book This Class')),
                  ),
              ],
            ),
          );
        },
      ),
    );
  }
}

class _InfoRow extends StatelessWidget {
  const _InfoRow({required this.icon, required this.label, required this.value});

  final IconData icon;
  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 10),
      child: Row(
        children: [
          Icon(icon, size: 20, color: scheme.primary),
          const SizedBox(width: 14),
          Text(label, style: TextStyle(color: scheme.onSurfaceVariant)),
          const Spacer(),
          Text(value, style: const TextStyle(fontWeight: FontWeight.w600)),
        ],
      ),
    );
  }
}
