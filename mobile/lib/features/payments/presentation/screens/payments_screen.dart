import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../../core/utils/formatters.dart';
import '../../../../core/widgets/async_value_view.dart';
import '../../../../core/widgets/status_badge.dart';
import '../../../../shared/models/invoice.dart';
import '../../../../shared/models/paginated.dart';
import '../../../../shared/models/payment.dart';
import '../providers/payments_provider.dart';

class PaymentsScreen extends ConsumerStatefulWidget {
  const PaymentsScreen({super.key});

  @override
  ConsumerState<PaymentsScreen> createState() => _PaymentsScreenState();
}

class _PaymentsScreenState extends ConsumerState<PaymentsScreen> with SingleTickerProviderStateMixin {
  late final TabController _tabController;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Payments & Invoices'),
        bottom: TabBar(controller: _tabController, tabs: const [Tab(text: 'Payments'), Tab(text: 'Invoices')]),
      ),
      body: TabBarView(
        controller: _tabController,
        children: const [_PaymentsTab(), _InvoicesTab()],
      ),
    );
  }
}

class _PaymentsTab extends ConsumerWidget {
  const _PaymentsTab();

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final payments = ref.watch(paymentsListProvider);

    return RefreshIndicator(
      onRefresh: () async => ref.invalidate(paymentsListProvider),
      child: AsyncValueView<Paginated<Payment>>(
        value: payments,
        onRetry: () => ref.invalidate(paymentsListProvider),
        isEmpty: (data) => data.items.isEmpty,
        emptyMessage: 'No payments yet.',
        emptyIcon: Icons.payments_outlined,
        data: (data) => ListView.separated(
          padding: const EdgeInsets.all(16),
          itemCount: data.items.length,
          separatorBuilder: (_, _) => const SizedBox(height: 10),
          itemBuilder: (context, i) {
            final payment = data.items[i];
            return Card(
              child: ListTile(
                contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                title: Text(Formatters.currency(payment.amount), style: const TextStyle(fontWeight: FontWeight.w700)),
                subtitle: Text('${payment.method} · ${Formatters.date(payment.date)}'),
                trailing: StatusBadge(status: payment.status),
              ),
            );
          },
        ),
      ),
    );
  }
}

class _InvoicesTab extends ConsumerWidget {
  const _InvoicesTab();

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final invoices = ref.watch(invoicesListProvider);

    return RefreshIndicator(
      onRefresh: () async => ref.invalidate(invoicesListProvider),
      child: AsyncValueView<Paginated<Invoice>>(
        value: invoices,
        onRetry: () => ref.invalidate(invoicesListProvider),
        isEmpty: (data) => data.items.isEmpty,
        emptyMessage: 'No invoices yet.',
        emptyIcon: Icons.receipt_long_outlined,
        data: (data) => ListView.separated(
          padding: const EdgeInsets.all(16),
          itemCount: data.items.length,
          separatorBuilder: (_, _) => const SizedBox(height: 10),
          itemBuilder: (context, i) {
            final invoice = data.items[i];
            return Card(
              child: ListTile(
                contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                title: Text(invoice.invoiceNumber, style: const TextStyle(fontWeight: FontWeight.w700)),
                subtitle: Text('${Formatters.currency(invoice.total)} · Due ${Formatters.date(invoice.dueDate)}'),
                trailing: StatusBadge(status: invoice.status),
                onTap: () => context.push('/invoices/${invoice.id}'),
              ),
            );
          },
        ),
      ),
    );
  }
}
