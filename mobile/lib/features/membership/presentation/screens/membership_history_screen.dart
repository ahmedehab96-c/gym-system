import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/network/cached.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/widgets/async_value_view.dart';
import '../../../../core/widgets/cached_notice.dart';
import '../../../../core/widgets/status_badge.dart';
import '../../../../shared/models/membership.dart';
import '../../../../shared/models/paginated.dart';
import '../providers/membership_provider.dart';

class MembershipHistoryScreen extends ConsumerWidget {
  const MembershipHistoryScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final history = ref.watch(membershipHistoryProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Membership History')),
      body: AsyncValueView<Cached<Paginated<Membership>>>(
        value: history,
        onRetry: () => ref.invalidate(membershipHistoryProvider),
        isEmpty: (cached) => cached.data.items.isEmpty,
        emptyMessage: 'No membership history yet.',
        data: (cached) => ListView(
          padding: const EdgeInsets.all(16),
          children: [
            if (cached.meta.isFromCache) CachedDataNotice(cachedAt: cached.meta.cachedAt),
            ...cached.data.items.map((membership) => Padding(
                  padding: const EdgeInsets.only(bottom: 10),
                  child: Card(
                    child: ListTile(
                      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                      title: Text(membership.planName ?? 'Plan', style: const TextStyle(fontWeight: FontWeight.w700)),
                      subtitle: Text('${Formatters.date(membership.startDate)} – ${Formatters.date(membership.expiryDate)}'),
                      trailing: StatusBadge(status: membership.status),
                    ),
                  ),
                )),
          ],
        ),
      ),
    );
  }
}
