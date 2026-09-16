import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/utils/formatters.dart';
import '../../../../core/widgets/async_value_view.dart';
import '../../../../core/widgets/status_badge.dart';
import '../../../../shared/models/invoice.dart';
import '../providers/payments_provider.dart';

class InvoiceDetailScreen extends ConsumerWidget {
  const InvoiceDetailScreen({super.key, required this.invoiceId});

  final int invoiceId;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final invoice = ref.watch(invoiceDetailProvider(invoiceId));

    return Scaffold(
      appBar: AppBar(title: const Text('Invoice')),
      body: AsyncValueView<Invoice>(
        value: invoice,
        onRetry: () => ref.invalidate(invoiceDetailProvider(invoiceId)),
        data: (invoice) => ListView(
          padding: const EdgeInsets.all(20),
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(invoice.invoiceNumber, style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w800)),
                StatusBadge(status: invoice.status),
              ],
            ),
            Text(invoice.business.name, style: TextStyle(color: Theme.of(context).colorScheme.onSurfaceVariant)),
            const SizedBox(height: 20),
            _Row(label: 'Issue Date', value: Formatters.date(invoice.issueDate)),
            _Row(label: 'Due Date', value: Formatters.date(invoice.dueDate)),
            const Divider(height: 28),
            const Text('Items', style: TextStyle(fontWeight: FontWeight.w700)),
            const SizedBox(height: 10),
            ...invoice.items.map((item) => Padding(
                  padding: const EdgeInsets.symmetric(vertical: 6),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Expanded(child: Text(item.description)),
                      Text(Formatters.currency(item.amount)),
                    ],
                  ),
                )),
            const Divider(height: 28),
            _Row(label: 'Subtotal', value: Formatters.currency(invoice.subtotal)),
            if (invoice.discount > 0) _Row(label: 'Discount', value: '-${Formatters.currency(invoice.discount)}'),
            _Row(label: 'Total', value: Formatters.currency(invoice.total), bold: true),
            if (invoice.balanceDue != null) _Row(label: 'Balance Due', value: Formatters.currency(invoice.balanceDue)),
          ],
        ),
      ),
    );
  }
}

class _Row extends StatelessWidget {
  const _Row({required this.label, required this.value, this.bold = false});

  final String label;
  final String value;
  final bool bold;

  @override
  Widget build(BuildContext context) {
    final style = TextStyle(fontWeight: bold ? FontWeight.w800 : FontWeight.w600, fontSize: bold ? 16 : 14);
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: TextStyle(color: Theme.of(context).colorScheme.onSurfaceVariant)),
          Text(value, style: style),
        ],
      ),
    );
  }
}
