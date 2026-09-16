import 'package:flutter_test/flutter_test.dart';
import 'package:gym_member_app/shared/models/gym_class.dart';

void main() {
  group('GymClass', () {
    test('isFull and spotsLeft reflect booked vs capacity', () {
      final full = GymClass.fromJson({'id': 1, 'name': 'HIIT', 'capacity': 10, 'booked': 10, 'status': 'Full'});
      final open = GymClass.fromJson({'id': 2, 'name': 'Yoga', 'capacity': 10, 'booked': 4, 'status': 'Scheduled'});

      expect(full.isFull, isTrue);
      expect(full.spotsLeft, 0);
      expect(open.isFull, isFalse);
      expect(open.spotsLeft, 6);
    });

    test('isBookedByMe defaults to false when the backend omits it (staff-facing responses)', () {
      final gymClass = GymClass.fromJson({'id': 3, 'name': 'Spin', 'capacity': 10, 'booked': 1, 'status': 'Scheduled'});

      expect(gymClass.isBookedByMe, isFalse);
    });

    test('isBookedByMe reflects the member-scoped flag when present', () {
      final gymClass = GymClass.fromJson({
        'id': 4, 'name': 'Boxing', 'capacity': 10, 'booked': 1, 'status': 'Scheduled', 'isBookedByMe': true,
      });

      expect(gymClass.isBookedByMe, isTrue);
    });
  });
}
