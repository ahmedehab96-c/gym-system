import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../../core/network/cached.dart';
import '../../../../core/sync/sync_manager.dart';
import '../../../../core/utils/debouncer.dart';
import '../../../../core/widgets/app_avatar.dart';
import '../../../../core/widgets/async_value_view.dart';
import '../../../../core/widgets/cached_notice.dart';
import '../../../../core/widgets/search_field.dart';
import '../../../../core/widgets/status_badge.dart';
import '../../../../shared/models/member.dart';
import '../../../../shared/models/paginated.dart';
import '../providers/trainer_members_provider.dart';

class TrainerMembersScreen extends ConsumerStatefulWidget {
  const TrainerMembersScreen({super.key});

  @override
  ConsumerState<TrainerMembersScreen> createState() => _TrainerMembersScreenState();
}

class _TrainerMembersScreenState extends ConsumerState<TrainerMembersScreen> {
  final _debouncer = Debouncer();
  String _query = '';

  @override
  void dispose() {
    _debouncer.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final members =
        _query.isEmpty ? ref.watch(trainerAssignedMembersProvider) : ref.watch(trainerAssignedMembersSearchProvider(_query));

    return Scaffold(
      appBar: AppBar(title: const Text('Assigned Members')),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 4),
            child: SearchField(
              hintText: 'Search members...',
              onChanged: (value) => _debouncer.run(() => setState(() => _query = value.trim())),
            ),
          ),
          Expanded(
            child: RefreshIndicator(
              onRefresh: () => ref.read(syncManagerProvider).syncNow(),
              child: AsyncValueView<Cached<Paginated<Member>>>(
                value: members,
                onRetry: () => _query.isEmpty
                    ? ref.invalidate(trainerAssignedMembersProvider)
                    : ref.invalidate(trainerAssignedMembersSearchProvider(_query)),
                isEmpty: (cached) => cached.data.items.isEmpty,
                emptyMessage: _query.isEmpty ? 'No members are assigned to you yet.' : 'No members match "$_query".',
                emptyIcon: Icons.people_outline_rounded,
                data: (cached) => ListView(
                  padding: const EdgeInsets.all(16),
                  children: [
                    if (cached.meta.isFromCache) CachedDataNotice(cachedAt: cached.meta.cachedAt),
                    ...cached.data.items.map((member) => Padding(
                          padding: const EdgeInsets.only(bottom: 10),
                          child: Card(
                            child: ListTile(
                              contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                              leading: AppAvatar(imageUrl: member.avatar, name: member.name),
                              title: Text(member.name, style: const TextStyle(fontWeight: FontWeight.w700)),
                              subtitle: Text(member.planName ?? 'No plan'),
                              trailing: StatusBadge(status: member.status),
                              onTap: () => context.push('/trainer/members/${member.id}'),
                            ),
                          ),
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
