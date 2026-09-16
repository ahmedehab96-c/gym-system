import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/widgets/async_value_view.dart';
import '../../../../shared/models/attendance_record.dart';
import '../../../trainer_auth/presentation/providers/trainer_auth_provider.dart';
import '../providers/trainer_attendance_provider.dart';

class TrainerAttendanceScreen extends ConsumerWidget {
  const TrainerAttendanceScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final today = ref.watch(trainerTodaysAttendanceProvider);
    final actionState = ref.watch(trainerAttendanceActionControllerProvider);
    final authState = ref.watch(trainerAuthControllerProvider);
    final canEdit = authState is TrainerAuthAuthenticated && authState.user.permissions.canEdit('Attendance');

    return Scaffold(
      appBar: AppBar(title: const Text("Today's Attendance")),
      body: RefreshIndicator(
        onRefresh: () async => ref.invalidate(trainerTodaysAttendanceProvider),
        child: AsyncValueView<List<AttendanceRecord>>(
          value: today,
          onRetry: () => ref.invalidate(trainerTodaysAttendanceProvider),
          isEmpty: (data) => data.isEmpty,
          emptyMessage: 'No one has checked in yet today.',
          emptyIcon: Icons.event_busy_outlined,
          data: (records) => ListView.separated(
            padding: const EdgeInsets.all(16),
            itemCount: records.length,
            separatorBuilder: (_, _) => const SizedBox(height: 10),
            itemBuilder: (context, i) {
              final record = records[i];
              final isCheckedIn = record.checkOut == null;

              return Card(
                child: ListTile(
                  contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
                  title: Text('Member #${record.id}', style: const TextStyle(fontWeight: FontWeight.w600)),
                  subtitle: Text('In: ${record.checkIn ?? '—'}  ·  Out: ${record.checkOut ?? '—'}'),
                  trailing: canEdit && isCheckedIn
                      ? TextButton(
                          onPressed: actionState.isLoading
                              ? null
                              : () async {
                                  final error = await ref.read(trainerAttendanceActionControllerProvider.notifier).checkOut(record.id);
                                  if (!context.mounted) return;
                                  if (error != null) {
                                    ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(error)));
                                  }
                                },
                          child: const Text('Check Out'),
                        )
                      : Text(record.status, style: TextStyle(color: Theme.of(context).colorScheme.onSurfaceVariant, fontSize: 12)),
                ),
              );
            },
          ),
        ),
      ),
    );
  }
}
