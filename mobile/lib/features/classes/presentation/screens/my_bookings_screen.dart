import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../../core/network/cached.dart';
import '../../../../core/sync/sync_manager.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/widgets/async_value_view.dart';
import '../../../../core/widgets/cached_notice.dart';
import '../../../../shared/models/gym_class.dart';
import '../../../../shared/models/paginated.dart';
import '../providers/classes_provider.dart';

class MyBookingsScreen extends ConsumerWidget {
  const MyBookingsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final bookings = ref.watch(myBookingsProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('My Bookings')),
      body: RefreshIndicator(
        onRefresh: () => ref.read(syncManagerProvider).syncNow(),
        child: AsyncValueView<Cached<Paginated<GymClass>>>(
          value: bookings,
          onRetry: () => ref.invalidate(myBookingsProvider),
          isEmpty: (cached) => cached.data.items.isEmpty,
          emptyMessage: "You haven't booked any classes yet.",
          emptyIcon: Icons.event_available_outlined,
          data: (cached) => ListView(
            padding: const EdgeInsets.all(16),
            children: [
              if (cached.meta.isFromCache) CachedDataNotice(cachedAt: cached.meta.cachedAt),
              ...cached.data.items.map((gymClass) => Padding(
                    padding: const EdgeInsets.only(bottom: 10),
                    child: Card(
                      child: ListTile(
                        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                        title: Text(gymClass.name, style: const TextStyle(fontWeight: FontWeight.w700)),
                        subtitle: Text('${Formatters.date(gymClass.date)} · ${gymClass.startTime ?? ''}'),
                        onTap: () => context.push('/classes/${gymClass.id}'),
                        trailing: const Icon(Icons.chevron_right_rounded),
                      ),
                    ),
                  )),
            ],
          ),
        ),
      ),
    );
  }
}
