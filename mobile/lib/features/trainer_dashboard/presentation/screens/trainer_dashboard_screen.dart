import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../../core/utils/formatters.dart';
import '../../../../core/widgets/app_avatar.dart';
import '../../../../core/widgets/async_value_view.dart';
import '../../../../shared/models/gym_class.dart';
import '../../data/trainer_dashboard_repository.dart';
import '../providers/trainer_dashboard_provider.dart';

class TrainerDashboardScreen extends ConsumerWidget {
  const TrainerDashboardScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final dashboard = ref.watch(trainerDashboardProvider);

    return Scaffold(
      body: SafeArea(
        child: RefreshIndicator(
          onRefresh: () async => ref.invalidate(trainerDashboardProvider),
          child: AsyncValueView<TrainerDashboardData>(
            value: dashboard,
            onRetry: () => ref.invalidate(trainerDashboardProvider),
            data: (data) => ListView(
              padding: const EdgeInsets.all(20),
              children: [
                _Header(data: data),
                const SizedBox(height: 20),
                _StatsRow(data: data),
                const SizedBox(height: 20),
                const _QuickLinks(),
                const SizedBox(height: 24),
                _ClassesSection(title: "Today's Classes", classes: data.todaysClasses, emptyText: 'No classes scheduled today.'),
                const SizedBox(height: 24),
                _ClassesSection(title: 'Upcoming Classes', classes: data.upcomingClasses, emptyText: 'Nothing coming up yet.'),
                const SizedBox(height: 24),
                _RecentActivity(data: data),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _Header extends StatelessWidget {
  const _Header({required this.data});

  final TrainerDashboardData data;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    return Row(
      children: [
        AppAvatar(imageUrl: data.trainer.photo, name: data.trainer.name, radius: 28),
        const SizedBox(width: 14),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text('Welcome back,', style: TextStyle(color: scheme.onSurfaceVariant, fontSize: 13)),
              Text(data.trainer.name, style: const TextStyle(fontSize: 19, fontWeight: FontWeight.w800)),
            ],
          ),
        ),
        Stack(
          clipBehavior: Clip.none,
          children: [
            IconButton(icon: const Icon(Icons.notifications_outlined), onPressed: () => context.push('/trainer/notifications')),
            if (data.unreadNotifications > 0)
              Positioned(
                right: 6,
                top: 6,
                child: Container(width: 9, height: 9, decoration: BoxDecoration(color: scheme.error, shape: BoxShape.circle)),
              ),
          ],
        ),
      ],
    );
  }
}

class _StatsRow extends StatelessWidget {
  const _StatsRow({required this.data});

  final TrainerDashboardData data;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Expanded(child: _StatTile(label: 'Assigned Members', value: '${data.assignedMembersCount}', onTap: () => context.push('/trainer/members'))),
        const SizedBox(width: 12),
        Expanded(child: _StatTile(label: "Today's Attendance", value: '${data.todaysAttendance}', onTap: () => context.push('/trainer/attendance'))),
      ],
    );
  }
}

class _StatTile extends StatelessWidget {
  const _StatTile({required this.label, required this.value, this.onTap});

  final String label;
  final String value;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(18),
      child: Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(color: scheme.surface, borderRadius: BorderRadius.circular(18), border: Border.all(color: scheme.outlineVariant.withValues(alpha: 0.5))),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(value, style: const TextStyle(fontSize: 22, fontWeight: FontWeight.w800)),
            const SizedBox(height: 4),
            Text(label, style: TextStyle(color: scheme.onSurfaceVariant, fontSize: 12.5)),
          ],
        ),
      ),
    );
  }
}

/// Phase 29 §4 "Quick class actions" — the trainer dashboard previously
/// had no shortcuts row at all (unlike the Member dashboard's), so
/// reaching Classes/Programs required going through Home's class list or
/// the Profile tab first.
class _QuickLinks extends StatelessWidget {
  const _QuickLinks();

  static const _links = [
    (icon: Icons.calendar_month_outlined, label: 'Classes', route: '/trainer/classes'),
    (icon: Icons.people_outline_rounded, label: 'Members', route: '/trainer/members'),
    (icon: Icons.event_available_outlined, label: 'Attendance', route: '/trainer/attendance'),
    (icon: Icons.local_fire_department_outlined, label: 'Programs', route: '/trainer/programs'),
    (icon: Icons.notifications_outlined, label: 'Alerts', route: '/trainer/notifications'),
  ];

  @override
  Widget build(BuildContext context) {
    return Wrap(
      alignment: WrapAlignment.spaceBetween,
      runSpacing: 16,
      children: _links
          .map((l) => SizedBox(
                width: 68,
                child: _QuickLinkTile(icon: l.icon, label: l.label, onTap: () => GoRouter.of(context).push(l.route)),
              ))
          .toList(),
    );
  }
}

class _QuickLinkTile extends StatelessWidget {
  const _QuickLinkTile({required this.icon, required this.label, required this.onTap});

  final IconData icon;
  final String label;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(16),
      child: Column(
        children: [
          Container(
            width: 52,
            height: 52,
            decoration: BoxDecoration(color: scheme.surfaceContainerHighest.withValues(alpha: 0.6), borderRadius: BorderRadius.circular(16)),
            child: Icon(icon, color: scheme.primary),
          ),
          const SizedBox(height: 6),
          Text(label, style: const TextStyle(fontSize: 11.5), textAlign: TextAlign.center),
        ],
      ),
    );
  }
}

class _ClassesSection extends StatelessWidget {
  const _ClassesSection({required this.title, required this.classes, required this.emptyText});

  final String title;
  final List<GymClass> classes;
  final String emptyText;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Text(title, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 16)),
            TextButton(onPressed: () => context.push('/trainer/classes'), child: const Text('See all')),
          ],
        ),
        if (classes.isEmpty)
          Padding(padding: const EdgeInsets.symmetric(vertical: 8), child: Text(emptyText, style: TextStyle(color: scheme.onSurfaceVariant)))
        else
          ...classes.map((c) => InkWell(
                onTap: () => context.push('/trainer/classes/${c.id}'),
                borderRadius: BorderRadius.circular(14),
                child: Padding(
                  padding: const EdgeInsets.symmetric(vertical: 8),
                  child: Row(
                    children: [
                      Container(width: 4, height: 36, decoration: BoxDecoration(color: scheme.primary, borderRadius: BorderRadius.circular(4))),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(c.name, style: const TextStyle(fontWeight: FontWeight.w600)),
                            Text(
                              '${Formatters.date(c.date)} · ${c.startTime ?? ''} · ${c.booked}/${c.capacity} booked',
                              style: TextStyle(color: scheme.onSurfaceVariant, fontSize: 12.5),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
              )),
      ],
    );
  }
}

class _RecentActivity extends StatelessWidget {
  const _RecentActivity({required this.data});

  final TrainerDashboardData data;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text('Recent Activity', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 16)),
        const SizedBox(height: 8),
        if (data.recentActivity.isEmpty)
          Text('No recent bookings.', style: TextStyle(color: scheme.onSurfaceVariant))
        else
          ...data.recentActivity.map((activity) => Padding(
                padding: const EdgeInsets.symmetric(vertical: 6),
                child: Text(
                  '${activity.memberName ?? 'A member'} booked ${activity.className ?? 'a class'}',
                  style: const TextStyle(fontSize: 13),
                ),
              )),
      ],
    );
  }
}
