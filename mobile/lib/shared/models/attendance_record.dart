class AttendanceRecord {
  const AttendanceRecord({
    required this.id,
    required this.date,
    this.checkIn,
    this.checkOut,
    this.duration,
    this.method,
    required this.status,
  });

  factory AttendanceRecord.fromJson(Map<String, dynamic> json) => AttendanceRecord(
        id: json['id'] as int,
        date: json['date'] as String? ?? '',
        checkIn: json['checkIn'] as String?,
        checkOut: json['checkOut'] as String?,
        duration: json['duration'] as String?,
        method: json['method'] as String?,
        status: json['status'] as String? ?? 'Checked In',
      );

  final int id;
  final String date;
  final String? checkIn;
  final String? checkOut;
  final String? duration;
  final String? method;
  final String status;
}

class AttendanceSummary {
  const AttendanceSummary({
    required this.visitsThisMonth,
    required this.totalVisits,
    this.lastVisitDate,
    required this.currentlyCheckedIn,
    required this.attendanceRate,
  });

  factory AttendanceSummary.fromJson(Map<String, dynamic> json) => AttendanceSummary(
        visitsThisMonth: (json['visitsThisMonth'] as num?)?.toInt() ?? 0,
        totalVisits: (json['totalVisits'] as num?)?.toInt() ?? 0,
        lastVisitDate: json['lastVisitDate'] as String?,
        currentlyCheckedIn: json['currentlyCheckedIn'] as bool? ?? false,
        attendanceRate: (json['attendanceRate'] as num?)?.toInt() ?? 0,
      );

  final int visitsThisMonth;
  final int totalVisits;
  final String? lastVisitDate;
  final bool currentlyCheckedIn;
  final int attendanceRate;
}
