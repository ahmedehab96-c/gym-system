import '../../../core/network/api_client.dart';
import '../../../core/network/api_exception.dart';
import '../../../core/network/api_result.dart';
import '../../../shared/models/gym_class.dart';
import '../../../shared/models/member.dart';
import '../../../shared/models/payment.dart';

class DashboardData {
  const DashboardData({
    required this.member,
    required this.attendanceThisMonth,
    required this.upcomingClasses,
    required this.recentPayments,
    required this.unreadNotifications,
  });

  factory DashboardData.fromJson(Map<String, dynamic> json) => DashboardData(
        member: Member.fromJson(json['member'] as Map<String, dynamic>),
        attendanceThisMonth: (json['attendanceThisMonth'] as num?)?.toInt() ?? 0,
        upcomingClasses: (json['upcomingClasses'] as List? ?? [])
            .map((e) => GymClass.fromJson(e as Map<String, dynamic>))
            .toList(),
        recentPayments: (json['recentPayments'] as List? ?? [])
            .map((e) => Payment.fromJson(e as Map<String, dynamic>))
            .toList(),
        unreadNotifications: (json['unreadNotifications'] as num?)?.toInt() ?? 0,
      );

  final Member member;
  final int attendanceThisMonth;
  final List<GymClass> upcomingClasses;
  final List<Payment> recentPayments;
  final int unreadNotifications;
}

class DashboardRepository {
  DashboardRepository(this._apiClient);

  final ApiClient _apiClient;

  Future<ApiResult<DashboardData>> fetch() async {
    try {
      final response = await _apiClient.get('/member/dashboard');
      return ApiSuccess(DashboardData.fromJson(response['data'] as Map<String, dynamic>));
    } on ApiException catch (e) {
      return ApiFailure(e);
    }
  }
}
