class Payment {
  const Payment({
    required this.id,
    required this.reference,
    this.invoiceId,
    required this.amount,
    required this.method,
    required this.date,
    required this.status,
  });

  factory Payment.fromJson(Map<String, dynamic> json) => Payment(
        id: json['id'] as int,
        reference: json['reference'] as String? ?? '',
        invoiceId: json['invoiceId'] as int?,
        amount: (json['amount'] as num?)?.toInt() ?? 0,
        method: json['method'] as String? ?? '',
        date: json['date'] as String? ?? '',
        status: json['status'] as String? ?? 'Pending',
      );

  final int id;
  final String reference;
  final int? invoiceId;
  final int amount;
  final String method;
  final String date;
  final String status;
}
