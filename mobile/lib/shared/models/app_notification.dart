class AppNotification {
  const AppNotification({
    required this.id,
    required this.type,
    required this.title,
    required this.message,
    required this.read,
    required this.createdAt,
  });

  factory AppNotification.fromJson(Map<String, dynamic> json) => AppNotification(
        id: json['id'] as int,
        type: json['type'] as String? ?? 'System Notification',
        title: json['title'] as String? ?? '',
        message: json['message'] as String? ?? '',
        read: json['read'] as bool? ?? false,
        createdAt: json['createdAt'] as String? ?? '',
      );

  final int id;
  final String type;
  final String title;
  final String message;
  final bool read;
  final String createdAt;

  AppNotification copyWith({bool? read}) => AppNotification(
        id: id,
        type: type,
        title: title,
        message: message,
        read: read ?? this.read,
        createdAt: createdAt,
      );
}
