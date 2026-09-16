import '../../../core/network/api_client.dart';
import '../../../core/network/api_exception.dart';
import '../../../core/network/api_result.dart';
import '../../../shared/models/gym_class.dart';
import '../../../shared/models/trainer.dart';

class RecentBooking {
  const RecentBooking({this.memberName, this.className, this.bookedAt});

  factory RecentBooking.fromJson(Map<String, dynamic> json) => RecentBooking(
        memberName: json['memberName'] as String?,
        className: json['className'] as String?,
        bookedAt: json['bookedAt'] as String?,
      );

  final String? memberName;
  final String? className;
  final String? bookedAt;
}

class TrainerDashboardData {
  const TrainerDashboardData({
    required this.trainer,
    required this.todaysClasses,
    required this.upcomingClasses,
    required this.assignedMembersCount,
    required this.todaysAttendance,
    required this.unreadNotifications,
    required this.recentActivity,
  });

  factory TrainerDashboardData.fromJson(Map<String, dynamic> json) => TrainerDashboardData(
        trainer: Trainer.fromJson(json['trainer'] as Map<String, dynamic>),
        todaysClasses: (json['todaysClasses'] as List? ?? []).map((e) => GymClass.fromJson(e as Map<String, dynamic>)).toList(),
        upcomingClasses: (json['upcomingClasses'] as List? ?? []).map((e) => GymClass.fromJson(e as Map<String, dynamic>)).toList(),
        assignedMembersCount: (json['assignedMembersCount'] as num?)?.toInt() ?? 0,
        todaysAttendance: (json['todaysAttendance'] as num?)?.toInt() ?? 0,
        unreadNotifications: (json['unreadNotifications'] as num?)?.toInt() ?? 0,
        recentActivity: (json['recentActivity'] as List? ?? []).map((e) => RecentBooking.fromJson(e as Map<String, dynamic>)).toList(),
      );

  final Trainer trainer;
  final List<GymClass> todaysClasses;
  final List<GymClass> upcomingClasses;
  final int assignedMembersCount;
  final int todaysAttendance;
  final int unreadNotifications;
  final List<RecentBooking> recentActivity;
}

class TrainerDashboardRepository {
  TrainerDashboardRepository(this._apiClient);

  final ApiClient _apiClient;

  Future<ApiResult<TrainerDashboardData>> fetch() async {
    try {
      final response = await _apiClient.get('/trainer/dashboard');
      return ApiSuccess(TrainerDashboardData.fromJson(response['data'] as Map<String, dynamic>));
    } on ApiException catch (e) {
      return ApiFailure(e);
    }
  }
}
