class InvoiceItem {
  const InvoiceItem({required this.id, required this.description, required this.amount});

  factory InvoiceItem.fromJson(Map<String, dynamic> json) => InvoiceItem(
        id: json['id'] as int,
        description: json['description'] as String? ?? '',
        amount: (json['amount'] as num?)?.toInt() ?? 0,
      );

  final int id;
  final String description;
  final int amount;
}

class InvoiceBusinessInfo {
  const InvoiceBusinessInfo({required this.name, this.phone, this.email, this.address, required this.currency});

  factory InvoiceBusinessInfo.fromJson(Map<String, dynamic> json) => InvoiceBusinessInfo(
        name: json['name'] as String? ?? 'Gym',
        phone: json['phone'] as String?,
        email: json['email'] as String?,
        address: json['address'] as String?,
        currency: json['currency'] as String? ?? 'EGP',
      );

  final String name;
  final String? phone;
  final String? email;
  final String? address;
  final String currency;
}

class Invoice {
  const Invoice({
    required this.id,
    required this.invoiceNumber,
    required this.issueDate,
    required this.dueDate,
    required this.items,
    required this.subtotal,
    required this.discount,
    required this.total,
    required this.status,
    this.amountPaid,
    this.balanceDue,
    required this.business,
  });

  factory Invoice.fromJson(Map<String, dynamic> json) => Invoice(
        id: json['id'] as int,
        invoiceNumber: json['invoiceNumber'] as String? ?? '',
        issueDate: json['issueDate'] as String? ?? '',
        dueDate: json['dueDate'] as String? ?? '',
        items: (json['items'] as List? ?? [])
            .map((e) => InvoiceItem.fromJson(e as Map<String, dynamic>))
            .toList(),
        subtotal: (json['subtotal'] as num?)?.toInt() ?? 0,
        discount: (json['discount'] as num?)?.toInt() ?? 0,
        total: (json['total'] as num?)?.toInt() ?? 0,
        status: json['status'] as String? ?? 'Unpaid',
        amountPaid: (json['amountPaid'] as num?)?.toInt(),
        balanceDue: (json['balanceDue'] as num?)?.toInt(),
        business: InvoiceBusinessInfo.fromJson(json['business'] as Map<String, dynamic>? ?? const {}),
      );

  final int id;
  final String invoiceNumber;
  final String issueDate;
  final String dueDate;
  final List<InvoiceItem> items;
  final int subtotal;
  final int discount;
  final int total;
  final String status;
  final int? amountPaid;
  final int? balanceDue;
  final InvoiceBusinessInfo business;
}
