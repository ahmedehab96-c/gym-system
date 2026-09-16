import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/network/cached.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/widgets/app_avatar.dart';
import '../../../../core/widgets/async_value_view.dart';
import '../../../../core/widgets/cached_notice.dart';
import '../../../../core/widgets/status_badge.dart';
import '../../../../shared/models/member.dart';
import '../providers/trainer_members_provider.dart';

class TrainerMemberDetailScreen extends ConsumerWidget {
  const TrainerMemberDetailScreen({super.key, required this.memberId});

  final int memberId;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final member = ref.watch(trainerMemberDetailProvider(memberId));
    final attendance = ref.watch(trainerMemberAttendanceProvider(memberId));

    return Scaffold(
      appBar: AppBar(title: const Text('Member Profile')),
      body: AsyncValueView<Cached<Member>>(
        value: member,
        onRetry: () => ref.invalidate(trainerMemberDetailProvider(memberId)),
        data: (cached) {
          final m = cached.data;
          final scheme = Theme.of(context).colorScheme;

          return ListView(
            padding: const EdgeInsets.all(20),
            children: [
              if (cached.meta.isFromCache) CachedDataNotice(cachedAt: cached.meta.cachedAt),
              Center(
                child: Column(
                  children: [
                    AppAvatar(imageUrl: m.avatar, name: m.name, radius: 44),
                    const SizedBox(height: 12),
                    Text(m.name, style: const TextStyle(fontSize: 19, fontWeight: FontWeight.w800)),
                    const SizedBox(height: 6),
                    StatusBadge(status: m.status),
                  ],
                ),
              ),
              const SizedBox(height: 24),
              _Row(label: 'Membership Plan', value: m.planName ?? '—'),
              _Row(label: 'Expiry Date', value: Formatters.date(m.expiryDate)),
              _Row(label: 'Attendance Rate', value: '${m.attendanceRate ?? 0}%'),
              if (m.phone != null) _Row(label: 'Phone', value: m.phone!),
              if (m.email.isNotEmpty) _Row(label: 'Email', value: m.email),
              const SizedBox(height: 24),
              const Text('Recent Attendance', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 16)),
              const SizedBox(height: 10),
              attendance.when(
                data: (cachedAttendance) => cachedAttendance.data.items.isEmpty
                    ? Text('No attendance recorded yet.', style: TextStyle(color: scheme.onSurfaceVariant))
                    : Column(
                        children: cachedAttendance.data.items
                            .take(10)
                            .map((record) => Card(
                                  margin: const EdgeInsets.only(bottom: 8),
                                  child: ListTile(
                                    contentPadding: const EdgeInsets.symmetric(horizontal: 16),
                                    title: Text(Formatters.date(record.date)),
                                    subtitle: Text('In: ${record.checkIn ?? '—'}  ·  Out: ${record.checkOut ?? '—'}'),
                                  ),
                                ))
                            .toList(),
                      ),
                loading: () => const Padding(padding: EdgeInsets.symmetric(vertical: 16), child: Center(child: CircularProgressIndicator())),
                error: (_, _) => Text('Could not load attendance history.', style: TextStyle(color: scheme.error)),
              ),
            ],
          );
        },
      ),
    );
  }
}

class _Row extends StatelessWidget {
  const _Row({required this.label, required this.value});

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 8),
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
