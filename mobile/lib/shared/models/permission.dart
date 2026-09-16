/// One row of the staff role_permissions matrix, exactly as returned by
/// UserResource — the Trainer App uses this to decide what to *show*
/// (e.g. hide a "Check In" button a Trainer role can't use), while the
/// backend remains the actual enforcement point regardless (Phase 26 §12).
class Permission {
  const Permission({
    required this.module,
    required this.canView,
    required this.canCreate,
    required this.canEdit,
    required this.canDelete,
  });

  factory Permission.fromJson(Map<String, dynamic> json) => Permission(
        module: json['module'] as String? ?? '',
        canView: json['canView'] as bool? ?? false,
        canCreate: json['canCreate'] as bool? ?? false,
        canEdit: json['canEdit'] as bool? ?? false,
        canDelete: json['canDelete'] as bool? ?? false,
      );

  final String module;
  final bool canView;
  final bool canCreate;
  final bool canEdit;
  final bool canDelete;
}

class PermissionSet {
  const PermissionSet(this._permissions);

  factory PermissionSet.fromJson(List<dynamic>? json) =>
      PermissionSet((json ?? []).map((e) => Permission.fromJson(e as Map<String, dynamic>)).toList());

  final List<Permission> _permissions;

  bool canView(String module) => _permissions.firstWhere((p) => p.module == module, orElse: () => const Permission(module: '', canView: false, canCreate: false, canEdit: false, canDelete: false)).canView;

  bool canCreate(String module) => _permissions.firstWhere((p) => p.module == module, orElse: () => const Permission(module: '', canView: false, canCreate: false, canEdit: false, canDelete: false)).canCreate;

  bool canEdit(String module) => _permissions.firstWhere((p) => p.module == module, orElse: () => const Permission(module: '', canView: false, canCreate: false, canEdit: false, canDelete: false)).canEdit;
}
