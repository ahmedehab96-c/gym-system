import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../../core/network/cached.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/widgets/async_value_view.dart';
import '../../../../core/widgets/cached_notice.dart';
import '../../../../core/widgets/empty_view.dart';
import '../../../../core/widgets/status_badge.dart';
import '../../../../shared/models/membership.dart';
import '../providers/membership_provider.dart';

class MembershipScreen extends ConsumerWidget {
  const MembershipScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final membership = ref.watch(currentMembershipProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text('My Membership'),
        actions: [
          TextButton(onPressed: () => context.push('/membership/history'), child: const Text('History')),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () async => ref.invalidate(currentMembershipProvider),
        child: AsyncValueView<Cached<Membership>?>(
          value: membership,
          onRetry: () => ref.invalidate(currentMembershipProvider),
          data: (cached) {
            if (cached == null) {
              return const EmptyView(message: 'You don\'t have an active membership yet.', icon: Icons.card_membership_outlined);
            }
            final data = cached.data;
            return ListView(
              padding: const EdgeInsets.all(20),
              children: [
                if (cached.meta.isFromCache) CachedDataNotice(cachedAt: cached.meta.cachedAt),
                Card(
                  child: Padding(
                    padding: const EdgeInsets.all(20),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Text(data.planName ?? 'Plan', style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w800)),
                            StatusBadge(status: data.status),
                          ],
                        ),
                        const SizedBox(height: 20),
                        _Row(label: 'Start Date', value: Formatters.date(data.startDate)),
                        _Row(label: 'Expiry Date', value: Formatters.date(data.expiryDate)),
                        _Row(label: 'Price', value: Formatters.currency(data.price)),
                        if (cached.meta.isFromCache)
                          Padding(
                            padding: const EdgeInsets.only(top: 12),
                            child: Text(
                              'Plan details only — not a live billing confirmation.',
                              style: TextStyle(fontSize: 11.5, color: Theme.of(context).colorScheme.onSurfaceVariant),
                            ),
                          ),
                      ],
                    ),
                  ),
                ),
              ],
            );
          },
        ),
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
