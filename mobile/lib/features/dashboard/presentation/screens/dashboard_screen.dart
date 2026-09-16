import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../../core/utils/formatters.dart';
import '../../../../core/widgets/app_avatar.dart';
import '../../../../core/widgets/async_value_view.dart';
import '../../../../core/widgets/status_badge.dart';
import '../../../../shared/models/gym_class.dart';
import '../../../../shared/models/payment.dart';
import '../../data/dashboard_repository.dart';
import '../providers/dashboard_provider.dart';

class DashboardScreen extends ConsumerWidget {
  const DashboardScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final dashboard = ref.watch(dashboardProvider);

    return Scaffold(
      body: SafeArea(
        child: RefreshIndicator(
          onRefresh: () async => ref.invalidate(dashboardProvider),
          child: AsyncValueView<DashboardData>(
            value: dashboard,
            onRetry: () => ref.invalidate(dashboardProvider),
            data: (data) => ListView(
              padding: const EdgeInsets.all(20),
              children: [
                _Header(data: data),
                const SizedBox(height: 20),
                _MembershipCard(data: data),
                const SizedBox(height: 16),
                _QuickStatsRow(data: data),
                const SizedBox(height: 24),
                _QuickLinks(),
                const SizedBox(height: 24),
                _UpcomingClasses(classes: data.upcomingClasses),
                const SizedBox(height: 24),
                _RecentPayments(payments: data.recentPayments),
                const SizedBox(height: 24),
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

  final DashboardData data;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    return Row(
      children: [
        AppAvatar(imageUrl: data.member.avatar, name: data.member.name, radius: 28),
        const SizedBox(width: 14),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text('Welcome back,', style: TextStyle(color: scheme.onSurfaceVariant, fontSize: 13)),
              Text(data.member.name, style: const TextStyle(fontSize: 19, fontWeight: FontWeight.w800)),
            ],
          ),
        ),
        Stack(
          clipBehavior: Clip.none,
          children: [
            IconButton(
              icon: const Icon(Icons.notifications_outlined),
              onPressed: () => context.go('/notifications'),
            ),
            if (data.unreadNotifications > 0)
              Positioned(
                right: 6,
                top: 6,
                child: Container(
                  width: 9,
                  height: 9,
                  decoration: BoxDecoration(color: scheme.error, shape: BoxShape.circle),
                ),
              ),
          ],
        ),
      ],
    );
  }
}

class _MembershipCard extends StatelessWidget {
  const _MembershipCard({required this.data});

  final DashboardData data;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    final member = data.member;
    final daysLeft = Formatters.daysUntil(member.expiryDate);

    return GestureDetector(
      onTap: () => context.push('/membership'),
      child: Container(
        width: double.infinity,
        padding: const EdgeInsets.all(20),
        decoration: BoxDecoration(
          gradient: LinearGradient(colors: [scheme.primary, scheme.primary.withValues(alpha: 0.75)]),
          borderRadius: BorderRadius.circular(22),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(member.planName ?? 'No plan', style: TextStyle(color: scheme.onPrimary, fontSize: 17, fontWeight: FontWeight.w700)),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                  decoration: BoxDecoration(color: scheme.onPrimary.withValues(alpha: 0.2), borderRadius: BorderRadius.circular(20)),
                  child: Text(member.status, style: TextStyle(color: scheme.onPrimary, fontSize: 12, fontWeight: FontWeight.w600)),
                ),
              ],
            ),
            const SizedBox(height: 14),
            Text(
              member.expiryDate == null
                  ? 'No active membership'
                  : daysLeft != null && daysLeft >= 0
                      ? 'Expires in $daysLeft day${daysLeft == 1 ? '' : 's'} · ${Formatters.date(member.expiryDate)}'
                      : 'Expired on ${Formatters.date(member.expiryDate)}',
              style: TextStyle(color: scheme.onPrimary.withValues(alpha: 0.9), fontSize: 13),
            ),
          ],
        ),
      ),
    );
  }
}

class _QuickStatsRow extends StatelessWidget {
  const _QuickStatsRow({required this.data});

  final DashboardData data;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Expanded(child: _StatTile(label: 'Visits this month', value: '${data.attendanceThisMonth}', onTap: () => context.push('/attendance'))),
        const SizedBox(width: 12),
        Expanded(child: _StatTile(label: 'Attendance rate', value: '${data.member.attendanceRate ?? 0}%', onTap: () => context.push('/attendance'))),
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

/// Phase 29 §2's exact quick-action set: My QR, Classes, Membership,
/// Attendance, Payments, Notifications — wrapped rather than a fixed Row
/// so it stays comfortable on narrow screens with 6 items instead of 4.
class _QuickLinks extends StatelessWidget {
  static const _links = [
    (icon: Icons.qr_code_2_rounded, label: 'My QR', route: '/qr'),
    (icon: Icons.calendar_month_outlined, label: 'Classes', route: '/classes'),
    (icon: Icons.card_membership_outlined, label: 'Membership', route: '/membership'),
    (icon: Icons.event_available_outlined, label: 'Attendance', route: '/attendance'),
    (icon: Icons.receipt_long_outlined, label: 'Payments', route: '/payments'),
    (icon: Icons.notifications_outlined, label: 'Alerts', route: '/notifications'),
  ];

  @override
  Widget build(BuildContext context) {
    return Wrap(
      alignment: WrapAlignment.spaceBetween,
      runSpacing: 16,
      children: _links
          .map((l) => SizedBox(
                width: 76,
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
          Text(label, style: const TextStyle(fontSize: 11.5)),
        ],
      ),
    );
  }
}

class _UpcomingClasses extends StatelessWidget {
  const _UpcomingClasses({required this.classes});

  final List<GymClass> classes;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            const Text('Upcoming Classes', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 16)),
            TextButton(onPressed: () => GoRouter.of(context).push('/classes-my-bookings'), child: const Text('See all')),
          ],
        ),
        if (classes.isEmpty)
          Padding(
            padding: const EdgeInsets.symmetric(vertical: 12),
            child: Text('No upcoming classes booked.', style: TextStyle(color: Theme.of(context).colorScheme.onSurfaceVariant)),
          )
        else
          ...classes.map((c) => _ClassRow(gymClass: c)),
      ],
    );
  }
}

class _ClassRow extends StatelessWidget {
  const _ClassRow({required this.gymClass});

  final GymClass gymClass;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    return InkWell(
      onTap: () => GoRouter.of(context).push('/classes/${gymClass.id}'),
      borderRadius: BorderRadius.circular(14),
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 8),
        child: Row(
          children: [
            Container(
              width: 4,
              height: 36,
              decoration: BoxDecoration(color: scheme.primary, borderRadius: BorderRadius.circular(4)),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(gymClass.name, style: const TextStyle(fontWeight: FontWeight.w600)),
                  Text(
                    '${Formatters.date(gymClass.date)} · ${gymClass.startTime ?? ''}',
                    style: TextStyle(color: scheme.onSurfaceVariant, fontSize: 12.5),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _RecentPayments extends StatelessWidget {
  const _RecentPayments({required this.payments});

  final List<Payment> payments;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            const Text('Recent Payments', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 16)),
            TextButton(onPressed: () => GoRouter.of(context).push('/payments'), child: const Text('See all')),
          ],
        ),
        if (payments.isEmpty)
          Padding(
            padding: const EdgeInsets.symmetric(vertical: 12),
            child: Text('No payments yet.', style: TextStyle(color: Theme.of(context).colorScheme.onSurfaceVariant)),
          )
        else
          ...payments.map((p) => Padding(
                padding: const EdgeInsets.symmetric(vertical: 6),
                child: Row(
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(Formatters.currency(p.amount), style: const TextStyle(fontWeight: FontWeight.w600)),
                          Text(Formatters.date(p.date), style: TextStyle(color: Theme.of(context).colorScheme.onSurfaceVariant, fontSize: 12)),
                        ],
                      ),
                    ),
                    StatusBadge(status: p.status),
                  ],
                ),
              )),
      ],
    );
  }
}
