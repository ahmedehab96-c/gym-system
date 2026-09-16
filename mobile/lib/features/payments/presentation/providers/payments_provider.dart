import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/providers/core_providers.dart';
import '../../../../shared/models/invoice.dart';
import '../../../../shared/models/paginated.dart';
import '../../../../shared/models/payment.dart';
import '../../data/payments_repository.dart';

final paymentsRepositoryProvider = Provider((ref) => PaymentsRepository(ref.watch(apiClientProvider)));

final paymentsListProvider = FutureProvider.autoDispose<Paginated<Payment>>((ref) async {
  final result = await ref.watch(paymentsRepositoryProvider).payments();
  return result.when(success: (data) => data, failure: (error) => throw error);
});

final invoicesListProvider = FutureProvider.autoDispose<Paginated<Invoice>>((ref) async {
  final result = await ref.watch(paymentsRepositoryProvider).invoices();
  return result.when(success: (data) => data, failure: (error) => throw error);
});

final invoiceDetailProvider = FutureProvider.autoDispose.family<Invoice, int>((ref, id) async {
  final result = await ref.watch(paymentsRepositoryProvider).invoiceDetail(id);
  return result.when(success: (data) => data, failure: (error) => throw error);
});
