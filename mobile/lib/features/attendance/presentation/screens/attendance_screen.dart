import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/network/cached.dart';
import '../../../../core/sync/sync_manager.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/widgets/async_value_view.dart';
import '../../../../core/widgets/cached_notice.dart';
import '../../../../shared/models/attendance_record.dart';
import '../../../../shared/models/paginated.dart';
import '../providers/attendance_provider.dart';

class AttendanceScreen extends ConsumerWidget {
  const AttendanceScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final summary = ref.watch(attendanceSummaryProvider);
    final history = ref.watch(attendanceHistoryProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Attendance')),
      body: RefreshIndicator(
        onRefresh: () => ref.read(syncManagerProvider).syncNow(),
        child: ListView(
          padding: const EdgeInsets.all(20),
          children: [
            summary.when(
              data: (cached) => _SummaryCards(summary: cached.data),
              loading: () => const SizedBox(height: 90, child: Center(child: CircularProgressIndicator())),
              error: (_, _) => const SizedBox.shrink(),
            ),
            const SizedBox(height: 24),
            const Text('History', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 16)),
            const SizedBox(height: 10),
            AsyncValueView<Cached<Paginated<AttendanceRecord>>>(
              value: history,
              onRetry: () => ref.invalidate(attendanceHistoryProvider),
              isEmpty: (cached) => cached.data.items.isEmpty,
              emptyMessage: 'No attendance recorded yet.',
              emptyIcon: Icons.event_busy_outlined,
              data: (cached) => Column(
                children: [
                  if (cached.meta.isFromCache) CachedDataNotice(cachedAt: cached.meta.cachedAt),
                  ...cached.data.items.map((record) => Card(
                        margin: const EdgeInsets.only(bottom: 10),
                        child: ListTile(
                          contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
                          title: Text(Formatters.date(record.date), style: const TextStyle(fontWeight: FontWeight.w600)),
                          subtitle: Text('In: ${record.checkIn ?? '—'}  ·  Out: ${record.checkOut ?? '—'}'),
                          trailing: Text(record.duration ?? ''),
                        ),
                      )),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _SummaryCards extends StatelessWidget {
  const _SummaryCards({required this.summary});

  final AttendanceSummary summary;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Expanded(child: _Tile(label: 'This Month', value: '${summary.visitsThisMonth}')),
        const SizedBox(width: 12),
        Expanded(child: _Tile(label: 'Total Visits', value: '${summary.totalVisits}')),
        const SizedBox(width: 12),
        Expanded(child: _Tile(label: 'Rate', value: '${summary.attendanceRate}%')),
      ],
    );
  }
}

class _Tile extends StatelessWidget {
  const _Tile({required this.label, required this.value});

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 16, horizontal: 10),
      decoration: BoxDecoration(color: scheme.surface, borderRadius: BorderRadius.circular(16), border: Border.all(color: scheme.outlineVariant.withValues(alpha: 0.5))),
      child: Column(
        children: [
          Text(value, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w800)),
          const SizedBox(height: 4),
          Text(label, style: TextStyle(fontSize: 11, color: scheme.onSurfaceVariant), textAlign: TextAlign.center),
        ],
      ),
    );
  }
}
