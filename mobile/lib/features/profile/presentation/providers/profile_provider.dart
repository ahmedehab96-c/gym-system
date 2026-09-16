import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/providers/core_providers.dart';
import '../../../auth/presentation/providers/auth_provider.dart';
import '../../data/profile_repository.dart';

final profileRepositoryProvider = Provider((ref) => ProfileRepository(ref.watch(apiClientProvider)));

class ProfileController extends StateNotifier<AsyncValue<void>> {
  ProfileController(this._ref) : super(const AsyncData(null));

  final Ref _ref;

  Future<String?> update({String? name, String? phone, String? address, String? emergencyContact}) async {
    state = const AsyncLoading();
    final result = await _ref.read(profileRepositoryProvider).update(
          name: name,
          phone: phone,
          address: address,
          emergencyContact: emergencyContact,
        );
    return result.when(
      success: (member) {
        state = const AsyncData(null);
        _ref.read(authControllerProvider.notifier).updateMember(member);
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
    final result = await _ref.read(profileRepositoryProvider).uploadPhoto(filePath);
    return result.when(
      success: (member) {
        state = const AsyncData(null);
        _ref.read(authControllerProvider.notifier).updateMember(member);
        return null;
      },
      failure: (error) {
        state = const AsyncData(null);
        return error.message;
      },
    );
  }
}

final profileControllerProvider = StateNotifierProvider.autoDispose<ProfileController, AsyncValue<void>>((ref) => ProfileController(ref));
