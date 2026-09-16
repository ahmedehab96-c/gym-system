import 'package:flutter/material.dart';

import '../../../trainer_members/presentation/screens/trainer_members_screen.dart';
import '../../../trainer_notifications/presentation/screens/trainer_notifications_screen.dart';
import '../../../trainer_profile/presentation/screens/trainer_profile_screen.dart';
import 'trainer_dashboard_screen.dart';

/// The 4 bottom-tab destinations for the Trainer App (Phase 26 §11) —
/// classes/attendance/programs are reached by pushing from Home/Profile,
/// mirroring how the Member app's MainShell keeps its own tab count small.
class TrainerMainShell extends StatefulWidget {
  const TrainerMainShell({super.key});

  @override
  State<TrainerMainShell> createState() => _TrainerMainShellState();
}

class _TrainerMainShellState extends State<TrainerMainShell> {
  int _index = 0;

  static const _screens = [
    TrainerDashboardScreen(),
    TrainerMembersScreen(),
    TrainerNotificationsScreen(),
    TrainerProfileScreen(),
  ];

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: IndexedStack(index: _index, children: _screens),
      bottomNavigationBar: NavigationBar(
        selectedIndex: _index,
        onDestinationSelected: (i) => setState(() => _index = i),
        destinations: const [
          NavigationDestination(icon: Icon(Icons.home_outlined), selectedIcon: Icon(Icons.home_rounded), label: 'Home'),
          NavigationDestination(icon: Icon(Icons.people_outline_rounded), selectedIcon: Icon(Icons.people_rounded), label: 'Members'),
          NavigationDestination(icon: Icon(Icons.notifications_outlined), selectedIcon: Icon(Icons.notifications_rounded), label: 'Alerts'),
          NavigationDestination(icon: Icon(Icons.person_outline_rounded), selectedIcon: Icon(Icons.person_rounded), label: 'Profile'),
        ],
      ),
    );
  }
}
