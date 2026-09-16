import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/network/api_exception.dart';
import '../../../../core/network/cached.dart';
import '../../../../core/providers/core_providers.dart';
import '../../../../shared/models/membership.dart';
import '../../../../shared/models/paginated.dart';
import '../../data/membership_repository.dart';

final membershipRepositoryProvider =
    Provider((ref) => MembershipRepository(ref.watch(apiClientProvider), ref.watch(memberCachedFetchProvider)));

/// Null means "no membership yet" (a real, expected state — not every
/// member has started one) rather than an error; a 404 from the backend
/// is deliberately swallowed into that instead of surfacing as an error
/// banner. Any other failure still propagates and shows as an error. A
/// 404 is a real server answer, never a NetworkException, so CachedFetch
/// never masks it with a stale cache entry.
final currentMembershipProvider = FutureProvider.autoDispose<Cached<Membership>?>((ref) async {
  final result = await ref.watch(membershipRepositoryProvider).current();
  return result.when(
    success: (data) => data,
    failure: (error) => error is NotFoundException ? null : throw error,
  );
});

final membershipHistoryProvider = FutureProvider.autoDispose<Cached<Paginated<Membership>>>((ref) async {
  final result = await ref.watch(membershipRepositoryProvider).history();
  return result.when(success: (data) => data, failure: (error) => throw error);
});
