import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../../core/network/cached.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/widgets/app_avatar.dart';
import '../../../../core/widgets/async_value_view.dart';
import '../../../../core/widgets/cached_notice.dart';
import '../../../../core/widgets/empty_view.dart';
import '../../../../core/widgets/status_badge.dart';
import '../../../../shared/models/gym_class.dart';
import '../providers/trainer_classes_provider.dart';

class TrainerClassDetailScreen extends ConsumerWidget {
  const TrainerClassDetailScreen({super.key, required this.classId});

  final int classId;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final detail = ref.watch(trainerClassDetailProvider(classId));

    return Scaffold(
      appBar: AppBar(title: const Text('Class Details')),
      body: AsyncValueView<Cached<GymClass>>(
        value: detail,
        onRetry: () => ref.invalidate(trainerClassDetailProvider(classId)),
        data: (cached) {
          final gymClass = cached.data;
          final scheme = Theme.of(context).colorScheme;
          final members = gymClass.bookedMembers ?? const [];

          return ListView(
            padding: const EdgeInsets.all(20),
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
              Text(gymClass.category ?? '', style: TextStyle(color: scheme.onSurfaceVariant)),
              const SizedBox(height: 20),
              _InfoRow(icon: Icons.event_outlined, label: 'Date', value: Formatters.date(gymClass.date)),
              _InfoRow(icon: Icons.access_time_rounded, label: 'Time', value: '${gymClass.startTime ?? ''} - ${gymClass.endTime ?? ''}'),
              _InfoRow(icon: Icons.groups_outlined, label: 'Capacity', value: '${gymClass.booked}/${gymClass.capacity} booked'),
              const SizedBox(height: 24),
              Text('Booked Members (${members.length})', style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 16)),
              const SizedBox(height: 10),
              if (members.isEmpty)
                const EmptyView(message: 'No members have booked this class yet.', icon: Icons.person_off_outlined)
              else
                ...members.map((member) => Card(
                      margin: const EdgeInsets.only(bottom: 8),
                      child: ListTile(
                        leading: AppAvatar(imageUrl: member.avatar, name: member.name, radius: 18),
                        title: Text(member.name, style: const TextStyle(fontWeight: FontWeight.w600)),
                        subtitle: Text('Booked ${Formatters.dateTime(member.bookedAt)}'),
                        trailing: const StatusBadge(status: 'Active'),
                        onTap: () => context.push('/trainer/members/${member.id}'),
                      ),
                    )),
            ],
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
      padding: const EdgeInsets.symmetric(vertical: 8),
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
