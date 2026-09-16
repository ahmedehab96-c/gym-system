import 'package:flutter_test/flutter_test.dart';
import 'package:gym_member_app/shared/models/permission.dart';

void main() {
  group('PermissionSet', () {
    test('reflects the real Trainer role matrix — view-only, no create/edit', () {
      final permissions = PermissionSet.fromJson([
        {'module': 'Attendance', 'canView': true, 'canCreate': false, 'canEdit': false, 'canDelete': false},
        {'module': 'Classes', 'canView': true, 'canCreate': false, 'canEdit': false, 'canDelete': false},
        {'module': 'Staff', 'canView': false, 'canCreate': false, 'canEdit': false, 'canDelete': false},
      ]);

      expect(permissions.canView('Attendance'), isTrue);
      expect(permissions.canCreate('Attendance'), isFalse);
      expect(permissions.canEdit('Classes'), isFalse);
      expect(permissions.canView('Staff'), isFalse);
    });

    test('a module with no matching row is treated as fully denied, not a crash', () {
      final permissions = PermissionSet.fromJson([]);

      expect(permissions.canView('Anything'), isFalse);
      expect(permissions.canEdit('Anything'), isFalse);
    });
  });
}
