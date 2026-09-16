class Member {
  const Member({
    required this.id,
    required this.memberId,
    required this.name,
    this.avatar,
    this.gender,
    this.phone,
    required this.email,
    this.address,
    this.dob,
    this.joinDate,
    this.planId,
    this.planName,
    this.startDate,
    this.expiryDate,
    required this.status,
    this.attendanceRate,
    this.trainerId,
    this.trainerName,
    this.balanceDue,
    this.emergencyContact,
  });

  factory Member.fromJson(Map<String, dynamic> json) => Member(
        id: json['id'] as int,
        memberId: json['memberId'] as String? ?? '',
        name: json['name'] as String? ?? '',
        avatar: json['avatar'] as String?,
        gender: json['gender'] as String?,
        phone: json['phone'] as String?,
        email: json['email'] as String? ?? '',
        address: json['address'] as String?,
        dob: json['dob'] as String?,
        joinDate: json['joinDate'] as String?,
        planId: json['planId'] as int?,
        planName: json['planName'] as String?,
        startDate: json['startDate'] as String?,
        expiryDate: json['expiryDate'] as String?,
        status: json['status'] as String? ?? 'Inactive',
        attendanceRate: (json['attendanceRate'] as num?)?.toInt(),
        trainerId: json['trainerId'] as int?,
        trainerName: json['trainerName'] as String?,
        balanceDue: (json['balanceDue'] as num?)?.toInt(),
        emergencyContact: json['emergencyContact'] as String?,
      );

  final int id;
  final String memberId;
  final String name;
  final String? avatar;
  final String? gender;
  final String? phone;
  final String email;
  final String? address;
  final String? dob;
  final String? joinDate;
  final int? planId;
  final String? planName;
  final String? startDate;
  final String? expiryDate;
  final String status;
  final int? attendanceRate;
  final int? trainerId;
  final String? trainerName;
  final int? balanceDue;
  final String? emergencyContact;

  bool get isActive => status == 'Active';
}
