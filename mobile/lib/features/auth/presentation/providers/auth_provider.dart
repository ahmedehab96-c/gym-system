import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/network/api_exception.dart';
import '../../../../core/network/api_result.dart';
import '../../../../core/network/cached.dart';
import '../../../../core/providers/core_providers.dart';
import '../../../../shared/models/member.dart';
import '../../data/auth_repository.dart';

sealed class AuthState {
  const AuthState();
}

/// Restoring a possible session on app start — the splash screen shows
/// while in this state (Phase 25 §2 "session restoration").
class AuthChecking extends AuthState {
  const AuthChecking();
}

class AuthAuthenticated extends AuthState {
  const AuthAuthenticated(this.member);

  final Member member;
}

class AuthUnauthenticated extends AuthState {
  const AuthUnauthenticated();
}

final authRepositoryProvider = Provider<AuthRepository>((ref) {
  return AuthRepository(
    apiClient: ref.watch(apiClientProvider),
    secureStorage: ref.watch(secureStorageProvider),
    cacheStore: ref.watch(cacheStoreProvider),
    cachedFetch: ref.watch(memberCachedFetchProvider),
  );
});

final authControllerProvider = StateNotifierProvider<AuthController, AuthState>((ref) {
  final controller = AuthController(ref.watch(authRepositoryProvider));

  // Wires the ApiClient's 401 callback to a forced logout — any screen's
  // repository call that gets a 401 ends the session immediately, rather
  // than every screen having to check for it individually.
  ref.watch(apiClientProvider).onUnauthorized = controller.forceLogout;

  controller.restoreSession();
  return controller;
});

class AuthController extends StateNotifier<AuthState> {
  AuthController(this._repository) : super(const AuthChecking());

  final AuthRepository _repository;

  Future<void> restoreSession() async {
    if (!await _repository.hasStoredSession()) {
      state = const AuthUnauthenticated();
      return;
    }

    final result = await _repository.currentMember();
    // ApiResult.when() doesn't await its callbacks — the failure branch
    // needs one (clearLocalSession), so this can't use .when() directly
    // without the state update racing ahead of it.
    switch (result) {
      case ApiSuccess<Cached<Member>>(:final data):
        // `data` may itself be a cached (offline) profile — CachedFetch
        // already only serves that when the network genuinely failed,
        // so either way this is the right thing to show.
        state = AuthAuthenticated(data.data);
      case ApiFailure<Cached<Member>>(:final error):
        if (error is NetworkException) {
          // Phase 27: no connectivity AND nothing was ever cached for
          // this profile (a first-ever offline launch) — leave the
          // stored token intact rather than destroying a perfectly
          // valid session just because of a transient connectivity
          // gap; the next restore with connectivity back will succeed
          // normally.
          state = const AuthUnauthenticated();
          return;
        }
        // A stored token that no longer validates (revoked/expired
        // server-side) -> fall back to logged out rather than getting
        // stuck on the splash screen.
        await _repository.clearLocalSession();
        state = const AuthUnauthenticated();
    }
  }

  Future<String?> login({required String email, required String password}) async {
    final result = await _repository.login(email: email, password: password);
    return result.when(
      success: (member) {
        state = AuthAuthenticated(member);
        return null;
      },
      failure: (error) => error.message,
    );
  }

  Future<void> logout() async {
    await _repository.logout();
    state = const AuthUnauthenticated();
  }

  /// Called by the ApiClient on any 401 — clears local state without
  /// waiting on a (likely also-401) server round trip.
  void forceLogout() {
    if (state is AuthAuthenticated) {
      _repository.clearLocalSession();
      state = const AuthUnauthenticated();
    }
  }

  void updateMember(Member member) {
    if (state is AuthAuthenticated) {
      state = AuthAuthenticated(member);
    }
  }
}
