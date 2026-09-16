import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/providers/core_providers.dart';
import '../../data/member_qr.dart';
import '../../data/qr_repository.dart';

final qrRepositoryProvider = Provider((ref) => QrRepository(ref.watch(apiClientProvider)));

final myQrProvider = FutureProvider.autoDispose<MemberQr>((ref) async {
  final result = await ref.watch(qrRepositoryProvider).show();
  return result.when(success: (data) => data, failure: (error) => throw error);
});

class QrRegenerateController extends StateNotifier<AsyncValue<void>> {
  QrRegenerateController(this._ref) : super(const AsyncData(null));

  final Ref _ref;

  Future<String?> regenerate() async {
    state = const AsyncLoading();
    final result = await _ref.read(qrRepositoryProvider).regenerate();
    return result.when(
      success: (_) {
        state = const AsyncData(null);
        _ref.invalidate(myQrProvider);
        return null;
      },
      failure: (error) {
        state = const AsyncData(null);
        return error.message;
      },
    );
  }
}

final qrRegenerateControllerProvider =
    StateNotifierProvider.autoDispose<QrRegenerateController, AsyncValue<void>>((ref) => QrRegenerateController(ref));
