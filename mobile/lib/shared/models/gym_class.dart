class BookedMember {
  const BookedMember({required this.id, required this.name, this.avatar, this.bookedAt});

  factory BookedMember.fromJson(Map<String, dynamic> json) => BookedMember(
        id: json['id'] as int,
        name: json['name'] as String? ?? '',
        avatar: json['avatar'] as String?,
        bookedAt: json['bookedAt'] as String?,
      );

  final int id;
  final String name;
  final String? avatar;
  final String? bookedAt;
}

class GymClass {
  const GymClass({
    required this.id,
    required this.name,
    this.category,
    this.trainerId,
    this.trainerName,
    this.date,
    this.day,
    this.startTime,
    this.endTime,
    this.duration,
    required this.capacity,
    required this.booked,
    required this.status,
    this.color,
    this.isBookedByMe = false,
    this.bookedMembers,
  });

  factory GymClass.fromJson(Map<String, dynamic> json) => GymClass(
        id: json['id'] as int,
        name: json['name'] as String? ?? '',
        category: json['category'] as String?,
        trainerId: json['trainerId'] as int?,
        trainerName: json['trainerName'] as String?,
        date: json['date'] as String?,
        day: json['day'] as String?,
        startTime: json['startTime'] as String?,
        endTime: json['endTime'] as String?,
        duration: json['duration'] as String?,
        capacity: (json['capacity'] as num?)?.toInt() ?? 0,
        booked: (json['booked'] as num?)?.toInt() ?? 0,
        status: json['status'] as String? ?? 'Scheduled',
        color: json['color'] as String?,
        isBookedByMe: json['isBookedByMe'] as bool? ?? false,
        bookedMembers: (json['bookedMembers'] as List?)?.map((e) => BookedMember.fromJson(e as Map<String, dynamic>)).toList(),
      );

  final int id;
  final String name;
  final String? category;
  final int? trainerId;
  final String? trainerName;
  final String? date;
  final String? day;
  final String? startTime;
  final String? endTime;
  final String? duration;
  final int capacity;
  final int booked;
  final String status;
  final String? color;
  final bool isBookedByMe;

  /// Only present when the backend eager-loaded `members` (e.g. the
  /// class-detail endpoint) — null on list responses, not an empty list.
  final List<BookedMember>? bookedMembers;

  bool get isFull => booked >= capacity;
  int get spotsLeft => (capacity - booked).clamp(0, capacity);
}
