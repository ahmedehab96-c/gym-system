import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

/// Shown when no session (Member or Trainer) is active — lets one device
/// serve both actor types (Phase 26) without guessing which login screen
/// to show first.
class WelcomeScreen extends StatelessWidget {
  const WelcomeScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;

    return Scaffold(
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Container(
                width: 72,
                height: 72,
                decoration: BoxDecoration(color: scheme.primaryContainer, borderRadius: BorderRadius.circular(24)),
                child: Icon(Icons.fitness_center_rounded, color: scheme.onPrimaryContainer, size: 36),
              ),
              const SizedBox(height: 24),
              Text('Premium Gym', style: Theme.of(context).textTheme.headlineMedium?.copyWith(fontWeight: FontWeight.w800)),
              const SizedBox(height: 8),
              Text('Continue as...', style: TextStyle(color: scheme.onSurfaceVariant)),
              const SizedBox(height: 32),
              ElevatedButton.icon(
                onPressed: () => context.push('/login'),
                icon: const Icon(Icons.person_outline_rounded),
                label: const Text('Member'),
              ),
              const SizedBox(height: 12),
              OutlinedButton.icon(
                onPressed: () => context.push('/trainer/login'),
                icon: const Icon(Icons.badge_outlined),
                label: const Text('Trainer / Staff'),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
