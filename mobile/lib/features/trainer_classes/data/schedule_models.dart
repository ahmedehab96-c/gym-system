import '../../../shared/models/gym_class.dart';

class ScheduleDay {
  const ScheduleDay({required this.date, required this.day, required this.classes});

  factory ScheduleDay.fromJson(Map<String, dynamic> json) => ScheduleDay(
        date: json['date'] as String? ?? '',
        day: json['day'] as String? ?? '',
        classes: (json['classes'] as List? ?? []).map((e) => GymClass.fromJson(e as Map<String, dynamic>)).toList(),
      );

  final String date;
  final String day;
  final List<GymClass> classes;
}

class WeeklySchedule {
  const WeeklySchedule({required this.weekStart, required this.weekEnd, required this.days});

  factory WeeklySchedule.fromJson(Map<String, dynamic> json) => WeeklySchedule(
        weekStart: json['weekStart'] as String? ?? '',
        weekEnd: json['weekEnd'] as String? ?? '',
        days: (json['days'] as List? ?? []).map((e) => ScheduleDay.fromJson(e as Map<String, dynamic>)).toList(),
      );

  final String weekStart;
  final String weekEnd;
  final List<ScheduleDay> days;
}

class MonthlySchedule {
  const MonthlySchedule({required this.month, required this.days});

  factory MonthlySchedule.fromJson(Map<String, dynamic> json) => MonthlySchedule(
        month: json['month'] as String? ?? '',
        days: (json['days'] as List? ?? []).map((e) => ScheduleDay.fromJson(e as Map<String, dynamic>)).toList(),
      );

  final String month;
  final List<ScheduleDay> days;
}
