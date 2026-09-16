class Membership {
  const Membership({
    required this.id,
    required this.memberId,
    this.planId,
    this.planName,
    required this.startDate,
    required this.expiryDate,
    required this.price,
    required this.status,
  });

  factory Membership.fromJson(Map<String, dynamic> json) => Membership(
        id: json['id'] as int,
        memberId: json['memberId'] as int,
        planId: json['planId'] as int?,
        planName: json['planName'] as String?,
        startDate: json['startDate'] as String? ?? '',
        expiryDate: json['expiryDate'] as String? ?? '',
        price: (json['price'] as num?)?.toInt() ?? 0,
        status: json['status'] as String? ?? 'Active',
      );

  final int id;
  final int memberId;
  final int? planId;
  final String? planName;
  final String startDate;
  final String expiryDate;
  final int price;
  final String status;
}
