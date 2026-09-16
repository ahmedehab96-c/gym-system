import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../../core/network/cached.dart';
import '../../../../core/sync/sync_manager.dart';
import '../../../../core/utils/debouncer.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/widgets/async_value_view.dart';
import '../../../../core/widgets/cached_notice.dart';
import '../../../../core/widgets/search_field.dart';
import '../../../../shared/models/gym_class.dart';
import '../../../../shared/models/paginated.dart';
import '../providers/classes_provider.dart';

class ClassesScreen extends ConsumerStatefulWidget {
  const ClassesScreen({super.key});

  @override
  ConsumerState<ClassesScreen> createState() => _ClassesScreenState();
}

class _ClassesScreenState extends ConsumerState<ClassesScreen> {
  final _debouncer = Debouncer();
  String _query = '';

  @override
  void dispose() {
    _debouncer.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final classes = _query.isEmpty ? ref.watch(classesListProvider) : ref.watch(classesSearchProvider(_query));

    return Scaffold(
      appBar: AppBar(
        title: const Text('Classes'),
        actions: [
          TextButton(
            onPressed: () => context.push('/classes-my-bookings'),
            child: const Text('My Bookings'),
          ),
        ],
      ),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 4),
            child: SearchField(
              hintText: 'Search classes...',
              onChanged: (value) => _debouncer.run(() => setState(() => _query = value.trim())),
            ),
          ),
          Expanded(
            child: RefreshIndicator(
              onRefresh: () => ref.read(syncManagerProvider).syncNow(),
              child: AsyncValueView<Cached<Paginated<GymClass>>>(
                value: classes,
                onRetry: () => _query.isEmpty ? ref.invalidate(classesListProvider) : ref.invalidate(classesSearchProvider(_query)),
                isEmpty: (cached) => cached.data.items.isEmpty,
                emptyMessage: _query.isEmpty ? 'No classes are scheduled right now.' : 'No classes match "$_query".',
                emptyIcon: Icons.event_busy_outlined,
                data: (cached) => ListView(
                  padding: const EdgeInsets.all(16),
                  children: [
                    if (cached.meta.isFromCache) CachedDataNotice(cachedAt: cached.meta.cachedAt),
                    ...cached.data.items.map((gymClass) => Padding(
                          padding: const EdgeInsets.only(bottom: 10),
                          child: _ClassCard(gymClass: gymClass),
                        )),
                  ],
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _ClassCard extends StatelessWidget {
  const _ClassCard({required this.gymClass});

  final GymClass gymClass;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    return Card(
      child: InkWell(
        borderRadius: BorderRadius.circular(18),
        onTap: () => context.push('/classes/${gymClass.id}'),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Row(
            children: [
              Container(
                width: 46,
                height: 46,
                decoration: BoxDecoration(color: scheme.primaryContainer, borderRadius: BorderRadius.circular(14)),
                child: Icon(Icons.self_improvement_rounded, color: scheme.onPrimaryContainer),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(gymClass.name, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 15)),
                    const SizedBox(height: 3),
                    Text(
                      '${gymClass.trainerName ?? 'Unassigned'} · ${Formatters.date(gymClass.date)}',
                      style: TextStyle(color: scheme.onSurfaceVariant, fontSize: 12.5),
                    ),
                    const SizedBox(height: 3),
                    Text(
                      '${gymClass.startTime ?? ''} - ${gymClass.endTime ?? ''} · ${gymClass.spotsLeft} spot${gymClass.spotsLeft == 1 ? '' : 's'} left',
                      style: TextStyle(color: scheme.onSurfaceVariant, fontSize: 12.5),
                    ),
                  ],
                ),
              ),
              const Icon(Icons.chevron_right_rounded),
            ],
          ),
        ),
      ),
    );
  }
}
