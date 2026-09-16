import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../../core/network/cached.dart';
import '../../../../core/widgets/app_avatar.dart';
import '../../../../core/widgets/async_value_view.dart';
import '../../../../core/widgets/cached_notice.dart';
import '../../../../shared/models/trainer.dart';
import '../../../trainer_auth/presentation/providers/trainer_auth_provider.dart';
import '../providers/trainer_profile_provider.dart';

class TrainerProfileScreen extends ConsumerWidget {
  const TrainerProfileScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final profile = ref.watch(currentTrainerProfileCachedProvider);
    final scheme = Theme.of(context).colorScheme;

    return Scaffold(
      appBar: AppBar(title: const Text('Profile')),
      body: AsyncValueView<Cached<Trainer>>(
        value: profile,
        onRetry: () => ref.invalidate(currentTrainerProfileCachedProvider),
        data: (cached) {
          final trainer = cached.data;
          return ListView(
          padding: const EdgeInsets.all(20),
          children: [
            if (cached.meta.isFromCache) CachedDataNotice(cachedAt: cached.meta.cachedAt),
            Center(
              child: Column(
                children: [
                  AppAvatar(imageUrl: trainer.photo, name: trainer.name, radius: 44),
                  const SizedBox(height: 12),
                  Text(trainer.name, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w700)),
                  Text(trainer.specialty ?? 'Trainer', style: TextStyle(color: scheme.onSurfaceVariant, fontSize: 13)),
                ],
              ),
            ),
            const SizedBox(height: 28),
            _MenuTile(icon: Icons.edit_outlined, label: 'Edit Profile', onTap: () => context.push('/trainer/profile/edit')),
            _MenuTile(icon: Icons.calendar_month_outlined, label: 'My Classes', onTap: () => context.push('/trainer/classes')),
            _MenuTile(icon: Icons.people_outline_rounded, label: 'Assigned Members', onTap: () => context.push('/trainer/members')),
            _MenuTile(icon: Icons.local_fire_department_outlined, label: 'My Programs', onTap: () => context.push('/trainer/programs')),
            const SizedBox(height: 16),
            _MenuTile(icon: Icons.logout_rounded, label: 'Log Out', destructive: true, onTap: () => _confirmLogout(context, ref)),
          ],
          );
        },
      ),
    );
  }

  Future<void> _confirmLogout(BuildContext context, WidgetRef ref) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Log out?'),
        content: const Text('You will need to log in again to access your trainer account.'),
        actions: [
          TextButton(onPressed: () => Navigator.of(context).pop(false), child: const Text('Cancel')),
          TextButton(onPressed: () => Navigator.of(context).pop(true), child: const Text('Log Out')),
        ],
      ),
    );

    if (confirmed == true) {
      await ref.read(trainerAuthControllerProvider.notifier).logout();
    }
  }
}

class _MenuTile extends StatelessWidget {
  const _MenuTile({required this.icon, required this.label, required this.onTap, this.destructive = false});

  final IconData icon;
  final String label;
  final VoidCallback onTap;
  final bool destructive;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    final color = destructive ? scheme.error : scheme.onSurface;
    return ListTile(
      contentPadding: EdgeInsets.zero,
      leading: Icon(icon, color: color),
      title: Text(label, style: TextStyle(color: color, fontWeight: FontWeight.w600)),
      trailing: destructive ? null : const Icon(Icons.chevron_right_rounded),
      onTap: onTap,
    );
  }
}
