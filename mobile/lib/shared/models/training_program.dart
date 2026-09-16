class EnrolledMember {
  const EnrolledMember({required this.id, required this.name, this.avatar, this.enrolledAt});

  factory EnrolledMember.fromJson(Map<String, dynamic> json) => EnrolledMember(
        id: json['id'] as int,
        name: json['name'] as String? ?? '',
        avatar: json['avatar'] as String?,
        enrolledAt: json['enrolledAt'] as String?,
      );

  final int id;
  final String name;
  final String? avatar;
  final String? enrolledAt;
}

class TrainingProgram {
  const TrainingProgram({
    required this.id,
    required this.name,
    this.description,
    this.image,
    this.duration,
    this.difficulty,
    this.trainerId,
    this.trainerName,
    this.membersEnrolled,
    required this.status,
    this.enrolledMembers,
  });

  factory TrainingProgram.fromJson(Map<String, dynamic> json) => TrainingProgram(
        id: json['id'] as int,
        name: json['name'] as String? ?? '',
        description: json['description'] as String?,
        image: json['image'] as String?,
        duration: json['duration'] as String?,
        difficulty: json['difficulty'] as String?,
        trainerId: json['trainerId'] as int?,
        trainerName: json['trainerName'] as String?,
        membersEnrolled: (json['membersEnrolled'] as num?)?.toInt(),
        status: json['status'] as String? ?? 'Active',
        enrolledMembers: (json['enrolledMembers'] as List?)?.map((e) => EnrolledMember.fromJson(e as Map<String, dynamic>)).toList(),
      );

  final int id;
  final String name;
  final String? description;
  final String? image;
  final String? duration;
  final String? difficulty;
  final int? trainerId;
  final String? trainerName;
  final int? membersEnrolled;
  final String status;

  /// Only present when the backend eager-loaded `members` (the program
  /// detail endpoint) — null on list responses, not an empty list.
  final List<EnrolledMember>? enrolledMembers;
}
