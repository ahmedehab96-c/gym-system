import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../../core/widgets/app_avatar.dart';
import '../../../auth/presentation/providers/auth_provider.dart';

class ProfileScreen extends ConsumerWidget {
  const ProfileScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final authState = ref.watch(authControllerProvider);
    if (authState is! AuthAuthenticated) return const SizedBox.shrink();
    final member = authState.member;
    final scheme = Theme.of(context).colorScheme;

    return Scaffold(
      appBar: AppBar(title: const Text('Profile')),
      body: ListView(
        padding: const EdgeInsets.all(20),
        children: [
          Center(
            child: Column(
              children: [
                AppAvatar(imageUrl: member.avatar, name: member.name, radius: 44),
                const SizedBox(height: 12),
                Text(member.name, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w700)),
                Text(member.email, style: TextStyle(color: scheme.onSurfaceVariant, fontSize: 13)),
                if (member.memberId.isNotEmpty)
                  Padding(
                    padding: const EdgeInsets.only(top: 4),
                    child: Text('Member ID: ${member.memberId}', style: TextStyle(color: scheme.onSurfaceVariant, fontSize: 12)),
                  ),
              ],
            ),
          ),
          const SizedBox(height: 28),
          _MenuTile(icon: Icons.edit_outlined, label: 'Edit Profile', onTap: () => context.push('/profile/edit')),
          _MenuTile(icon: Icons.qr_code_2_rounded, label: 'My QR Code', onTap: () => context.push('/qr')),
          _MenuTile(icon: Icons.card_membership_outlined, label: 'Membership', onTap: () => context.push('/membership')),
          _MenuTile(icon: Icons.event_available_outlined, label: 'Attendance History', onTap: () => context.push('/attendance')),
          _MenuTile(icon: Icons.groups_outlined, label: 'Trainers', onTap: () => context.push('/trainers')),
          _MenuTile(icon: Icons.local_fire_department_outlined, label: 'My Programs', onTap: () => context.push('/programs-mine')),
          _MenuTile(icon: Icons.receipt_long_outlined, label: 'Payments & Invoices', onTap: () => context.push('/payments')),
          const SizedBox(height: 16),
          _MenuTile(
            icon: Icons.logout_rounded,
            label: 'Log Out',
            destructive: true,
            onTap: () => _confirmLogout(context, ref),
          ),
        ],
      ),
    );
  }

  Future<void> _confirmLogout(BuildContext context, WidgetRef ref) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Log out?'),
        content: const Text('You will need to log in again to access your account.'),
        actions: [
          TextButton(onPressed: () => Navigator.of(context).pop(false), child: const Text('Cancel')),
          TextButton(onPressed: () => Navigator.of(context).pop(true), child: const Text('Log Out')),
        ],
      ),
    );

    if (confirmed == true) {
      await ref.read(authControllerProvider.notifier).logout();
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
