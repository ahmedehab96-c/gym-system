import 'package:flutter_test/flutter_test.dart';
import 'package:gym_member_app/shared/models/member.dart';

void main() {
  group('Member.fromJson', () {
    test('parses a full payload matching the Laravel MemberResource shape', () {
      final member = Member.fromJson({
        'id': 1,
        'memberId': 'GYM-1234',
        'name': 'Alex Doe',
        'avatar': 'https://example.com/a.jpg',
        'gender': 'Male',
        'phone': '+15551234567',
        'email': 'alex@example.com',
        'address': '123 Main St',
        'dob': '1990-01-01',
        'joinDate': '2024-01-01',
        'planId': 3,
        'planName': 'Pro',
        'startDate': '2026-01-01',
        'expiryDate': '2026-12-31',
        'status': 'Active',
        'attendanceRate': 87,
        'trainerId': 5,
        'trainerName': 'Sam Trainer',
        'balanceDue': 0,
        'emergencyContact': '+15557654321',
      });

      expect(member.id, 1);
      expect(member.memberId, 'GYM-1234');
      expect(member.name, 'Alex Doe');
      expect(member.planName, 'Pro');
      expect(member.status, 'Active');
      expect(member.isActive, isTrue);
      expect(member.attendanceRate, 87);
    });

    test('tolerates missing optional fields without throwing', () {
      final member = Member.fromJson({
        'id': 2,
        'name': 'Minimal Member',
        'email': 'minimal@example.com',
        'status': 'Inactive',
      });

      expect(member.id, 2);
      expect(member.planName, isNull);
      expect(member.avatar, isNull);
      expect(member.isActive, isFalse);
    });
  });
}
