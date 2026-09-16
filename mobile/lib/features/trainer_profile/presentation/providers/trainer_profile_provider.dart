import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/network/cached.dart';
import '../../../../core/providers/core_providers.dart';
import '../../../../shared/models/trainer.dart';
import '../../../trainer_auth/presentation/providers/trainer_auth_provider.dart';
import '../../data/trainer_profile_repository.dart';

final trainerProfileRepositoryProvider =
    Provider((ref) => TrainerProfileRepository(ref.watch(apiClientProvider), ref.watch(trainerCachedFetchProvider)));

/// The current trainer's OWN roster row, with cache metadata (Phase 27
/// §1/§3 "Profile") — the profile screen watches this directly to show
/// a "showing saved data" notice when offline.
final currentTrainerProfileCachedProvider = FutureProvider.autoDispose<Cached<Trainer>>((ref) async {
  // Rebuilds whenever the trainer logs in/out, so a fresh login always
  // re-fetches rather than serving a stale cached profile.
  ref.watch(trainerAuthControllerProvider);

  final result = await ref.watch(trainerProfileRepositoryProvider).show();
  return result.when(success: (data) => data, failure: (error) => throw error);
});

/// Just the trainer row itself — every other trainer feature
/// (classes/members/programs/schedule) needs this row's `id` to pass as
/// `?trainer_id=` to the existing staff endpoints it reuses, and doesn't
/// care whether it came from cache.
final currentTrainerProfileProvider = FutureProvider.autoDispose<Trainer>((ref) async {
  final cached = await ref.watch(currentTrainerProfileCachedProvider.future);
  return cached.data;
});

class TrainerProfileController extends StateNotifier<AsyncValue<void>> {
  TrainerProfileController(this._ref) : super(const AsyncData(null));

  final Ref _ref;

  Future<String?> update({String? name, String? specialty, String? experience, String? phone, String? bio}) async {
    state = const AsyncLoading();
    final result = await _ref.read(trainerProfileRepositoryProvider).update(
          name: name,
          specialty: specialty,
          experience: experience,
          phone: phone,
          bio: bio,
        );
    return result.when(
      success: (_) {
        state = const AsyncData(null);
        _ref.invalidate(currentTrainerProfileProvider);
        return null;
      },
      failure: (error) {
        state = const AsyncData(null);
        return error.message;
      },
    );
  }

  Future<String?> uploadPhoto(String filePath) async {
    state = const AsyncLoading();
    final result = await _ref.read(trainerProfileRepositoryProvider).uploadPhoto(filePath);
    return result.when(
      success: (_) {
        state = const AsyncData(null);
        _ref.invalidate(currentTrainerProfileProvider);
        return null;
      },
      failure: (error) {
        state = const AsyncData(null);
        return error.message;
      },
    );
  }
}

final trainerProfileControllerProvider =
    StateNotifierProvider.autoDispose<TrainerProfileController, AsyncValue<void>>((ref) => TrainerProfileController(ref));
