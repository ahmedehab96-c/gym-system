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
import '../../data/schedule_models.dart';
import '../providers/trainer_classes_provider.dart';

/// Daily/Weekly/Monthly views (Phase 26 §3), each backed directly by the
/// existing /schedule/daily|weekly|monthly endpoints; "All" is the flat
/// /classes list (today + upcoming together).
class TrainerClassesScreen extends ConsumerStatefulWidget {
  const TrainerClassesScreen({super.key});

  @override
  ConsumerState<TrainerClassesScreen> createState() => _TrainerClassesScreenState();
}

class _TrainerClassesScreenState extends ConsumerState<TrainerClassesScreen> with SingleTickerProviderStateMixin {
  late final TabController _tabController;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 4, vsync: this);
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('My Classes'),
        bottom: TabBar(
          controller: _tabController,
          isScrollable: true,
          tabs: const [Tab(text: 'All'), Tab(text: 'Today'), Tab(text: 'This Week'), Tab(text: 'This Month')],
        ),
      ),
      body: TabBarView(
        controller: _tabController,
        children: const [_AllClassesTab(), _DailyTab(), _WeeklyTab(), _MonthlyTab()],
      ),
    );
  }
}

class _AllClassesTab extends ConsumerStatefulWidget {
  const _AllClassesTab();

  @override
  ConsumerState<_AllClassesTab> createState() => _AllClassesTabState();
}

class _AllClassesTabState extends ConsumerState<_AllClassesTab> {
  final _debouncer = Debouncer();
  String _query = '';

  @override
  void dispose() {
    _debouncer.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final classes = _query.isEmpty ? ref.watch(trainerMyClassesProvider) : ref.watch(trainerMyClassesSearchProvider(_query));

    return Column(
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
              onRetry: () => _query.isEmpty
                  ? ref.invalidate(trainerMyClassesProvider)
                  : ref.invalidate(trainerMyClassesSearchProvider(_query)),
              isEmpty: (cached) => cached.data.items.isEmpty,
              emptyMessage: _query.isEmpty ? "You don't have any classes yet." : 'No classes match "$_query".',
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
    );
  }
}

class _DailyTab extends ConsumerWidget {
  const _DailyTab();

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final schedule = ref.watch(trainerDailyScheduleProvider);

    return RefreshIndicator(
      onRefresh: () => ref.read(syncManagerProvider).syncNow(),
      child: AsyncValueView<Cached<ScheduleDay>>(
        value: schedule,
        onRetry: () => ref.invalidate(trainerDailyScheduleProvider),
        isEmpty: (cached) => cached.data.classes.isEmpty,
        emptyMessage: 'No classes scheduled today.',
        data: (cached) => ListView(
          padding: const EdgeInsets.all(16),
          children: [
            if (cached.meta.isFromCache) CachedDataNotice(cachedAt: cached.meta.cachedAt),
            ...cached.data.classes.map((gymClass) => Padding(
                  padding: const EdgeInsets.only(bottom: 10),
                  child: _ClassCard(gymClass: gymClass),
                )),
          ],
        ),
      ),
    );
  }
}

class _WeeklyTab extends ConsumerWidget {
  const _WeeklyTab();

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final schedule = ref.watch(trainerWeeklyScheduleProvider);

    return RefreshIndicator(
      onRefresh: () => ref.read(syncManagerProvider).syncNow(),
      child: AsyncValueView<Cached<WeeklySchedule>>(
        value: schedule,
        onRetry: () => ref.invalidate(trainerWeeklyScheduleProvider),
        isEmpty: (cached) => cached.data.days.every((d) => d.classes.isEmpty),
        emptyMessage: 'No classes scheduled this week.',
        data: (cached) => ListView(
          padding: const EdgeInsets.all(16),
          children: [
            if (cached.meta.isFromCache) CachedDataNotice(cachedAt: cached.meta.cachedAt),
            ...cached.data.days.where((d) => d.classes.isNotEmpty).map((day) => _DaySection(day: day)),
          ],
        ),
      ),
    );
  }
}

class _MonthlyTab extends ConsumerWidget {
  const _MonthlyTab();

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final schedule = ref.watch(trainerMonthlyScheduleProvider);

    return RefreshIndicator(
      onRefresh: () => ref.read(syncManagerProvider).syncNow(),
      child: AsyncValueView<Cached<MonthlySchedule>>(
        value: schedule,
        onRetry: () => ref.invalidate(trainerMonthlyScheduleProvider),
        isEmpty: (cached) => cached.data.days.every((d) => d.classes.isEmpty),
        emptyMessage: 'No classes scheduled this month.',
        data: (cached) => ListView(
          padding: const EdgeInsets.all(16),
          children: [
            if (cached.meta.isFromCache) CachedDataNotice(cachedAt: cached.meta.cachedAt),
            ...cached.data.days.where((d) => d.classes.isNotEmpty).map((day) => _DaySection(day: day)),
          ],
        ),
      ),
    );
  }
}

class _DaySection extends StatelessWidget {
  const _DaySection({required this.day});

  final ScheduleDay day;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text('${day.day} · ${Formatters.date(day.date)}', style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13)),
          const SizedBox(height: 8),
          ...day.classes.map((c) => Padding(padding: const EdgeInsets.only(bottom: 8), child: _ClassCard(gymClass: c))),
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
        onTap: () => context.push('/trainer/classes/${gymClass.id}'),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Row(
            children: [
              Container(
                width: 46,
                height: 46,
                decoration: BoxDecoration(color: scheme.primaryContainer, borderRadius: BorderRadius.circular(14)),
                child: Icon(Icons.fitness_center_rounded, color: scheme.onPrimaryContainer),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(gymClass.name, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 15)),
                    const SizedBox(height: 3),
                    Text(
                      '${Formatters.date(gymClass.date)} · ${gymClass.startTime ?? ''} - ${gymClass.endTime ?? ''}',
                      style: TextStyle(color: scheme.onSurfaceVariant, fontSize: 12.5),
                    ),
                    const SizedBox(height: 3),
                    Text('${gymClass.booked}/${gymClass.capacity} booked · ${gymClass.status}', style: TextStyle(color: scheme.onSurfaceVariant, fontSize: 12.5)),
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
