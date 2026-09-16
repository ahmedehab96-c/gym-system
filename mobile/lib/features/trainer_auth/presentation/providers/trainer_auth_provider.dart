import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/network/api_exception.dart';
import '../../../../core/network/api_result.dart';
import '../../../../core/network/cached.dart';
import '../../../../core/providers/core_providers.dart';
import '../../../../shared/models/staff_user.dart';
import '../../data/trainer_auth_repository.dart';

/// Mirrors App\Features\Auth's AuthState/AuthController shape exactly
/// (Phase 25), but for the Trainer actor — kept entirely separate so
/// logging in as one never touches the other's state, and so the
/// existing Member auth code isn't touched by this phase.
sealed class TrainerAuthState {
  const TrainerAuthState();
}

class TrainerAuthChecking extends TrainerAuthState {
  const TrainerAuthChecking();
}

class TrainerAuthAuthenticated extends TrainerAuthState {
  const TrainerAuthAuthenticated(this.user);

  final StaffUser user;
}

class TrainerAuthUnauthenticated extends TrainerAuthState {
  const TrainerAuthUnauthenticated();
}

final trainerAuthRepositoryProvider = Provider<TrainerAuthRepository>((ref) {
  return TrainerAuthRepository(
    apiClient: ref.watch(apiClientProvider),
    secureStorage: ref.watch(secureStorageProvider),
    cacheStore: ref.watch(cacheStoreProvider),
    cachedFetch: ref.watch(trainerCachedFetchProvider),
  );
});

final StateNotifierProvider<TrainerAuthController, TrainerAuthState> trainerAuthControllerProvider =
    StateNotifierProvider<TrainerAuthController, TrainerAuthState>((ref) {
  final controller = TrainerAuthController(ref.watch(trainerAuthRepositoryProvider));

  // Only takes over the ApiClient's 401 callback while a trainer session
  // is actually active, so a stray 401 during Member use doesn't try to
  // force-logout a trainer that was never logged in on this device.
  final apiClient = ref.watch(apiClientProvider);
  final previous = apiClient.onUnauthorized;
  apiClient.onUnauthorized = () {
    if (ref.read(trainerAuthControllerProvider) is TrainerAuthAuthenticated) {
      controller.forceLogout();
    } else {
      previous?.call();
    }
  };

  controller.restoreSession();
  return controller;
});

class TrainerAuthController extends StateNotifier<TrainerAuthState> {
  TrainerAuthController(this._repository) : super(const TrainerAuthChecking());

  final TrainerAuthRepository _repository;

  Future<void> restoreSession() async {
    if (!await _repository.hasStoredTrainerSession()) {
      state = const TrainerAuthUnauthenticated();
      return;
    }

    final result = await _repository.currentUser();
    switch (result) {
      case ApiSuccess<Cached<StaffUser>>(:final data):
        state = TrainerAuthAuthenticated(data.data);
      case ApiFailure<Cached<StaffUser>>(:final error):
        if (error is NetworkException) {
          // Phase 27: same reasoning as the Member app's AuthController
          // — don't destroy a valid stored token just because of a
          // transient connectivity gap.
          state = const TrainerAuthUnauthenticated();
          return;
        }
        await _repository.clearLocalSession();
        state = const TrainerAuthUnauthenticated();
    }
  }

  Future<String?> login({required String email, required String password}) async {
    final result = await _repository.login(email: email, password: password);
    return result.when(
      success: (user) {
        state = TrainerAuthAuthenticated(user);
        return null;
      },
      failure: (error) => error.message,
    );
  }

  Future<void> logout() async {
    await _repository.logout();
    state = const TrainerAuthUnauthenticated();
  }

  void forceLogout() {
    if (state is TrainerAuthAuthenticated) {
      _repository.clearLocalSession();
      state = const TrainerAuthUnauthenticated();
    }
  }

  void updateUser(StaffUser user) {
    if (state is TrainerAuthAuthenticated) {
      state = TrainerAuthAuthenticated(user);
    }
  }
}
