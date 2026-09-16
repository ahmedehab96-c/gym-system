import 'permission.dart';

/// The authenticated staff account (Phase 26) — distinct from Member:
/// this is App\Models\User on the backend, returned by /auth/me.
class StaffUser {
  const StaffUser({
    required this.id,
    required this.name,
    required this.email,
    this.phone,
    this.photo,
    required this.role,
    required this.status,
    required this.permissions,
  });

  factory StaffUser.fromJson(Map<String, dynamic> json) => StaffUser(
        id: json['id'] as int,
        name: json['name'] as String? ?? '',
        email: json['email'] as String? ?? '',
        phone: json['phone'] as String?,
        photo: json['photo'] as String?,
        role: json['role'] as String? ?? '',
        status: json['status'] as String? ?? 'Active',
        permissions: PermissionSet.fromJson(json['permissions'] as List<dynamic>?),
      );

  final int id;
  final String name;
  final String email;
  final String? phone;
  final String? photo;
  final String role;
  final String status;
  final PermissionSet permissions;
}
