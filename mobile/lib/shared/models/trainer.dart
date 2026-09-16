class Trainer {
  const Trainer({
    required this.id,
    this.userId,
    required this.name,
    this.photo,
    this.specialty,
    this.specialties = const [],
    this.experience,
    this.phone,
    this.email,
    this.bio,
    required this.status,
    required this.rating,
    this.sessionsCompleted,
  });

  factory Trainer.fromJson(Map<String, dynamic> json) => Trainer(
        id: json['id'] as int,
        userId: json['userId'] as int?,
        name: json['name'] as String? ?? '',
        photo: json['photo'] as String?,
        specialty: json['specialty'] as String?,
        specialties: (json['specialties'] as List?)?.map((e) => e.toString()).toList() ?? const [],
        experience: json['experience'] as String?,
        phone: json['phone'] as String?,
        email: json['email'] as String?,
        bio: json['bio'] as String?,
        status: json['status'] as String? ?? 'Active',
        rating: (json['rating'] as num?)?.toDouble() ?? 0,
        sessionsCompleted: (json['sessionsCompleted'] as num?)?.toInt(),
      );

  final int id;

  /// The staff User account this roster row is linked to, if any (Phase
  /// 26 — the Flutter Trainer App). Null for a trainer with no app login.
  final int? userId;
  final String name;
  final String? photo;
  final String? specialty;
  final List<String> specialties;
  final String? experience;
  final String? phone;
  final String? email;
  final String? bio;
  final String status;
  final double rating;
  final int? sessionsCompleted;
}
