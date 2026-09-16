import 'package:flutter_test/flutter_test.dart';
import 'package:gym_member_app/shared/models/paginated.dart';

void main() {
  test('Paginated.fromJson maps items and meta matching ApiResponse::paginated()', () {
    final result = Paginated<int>.fromJson({
      'data': [
        {'value': 1},
        {'value': 2},
      ],
      'meta': {'page': 1, 'perPage': 15, 'total': 2, 'totalPages': 1},
    }, (json) => json['value'] as int);

    expect(result.items, [1, 2]);
    expect(result.meta.page, 1);
    expect(result.meta.totalPages, 1);
    expect(result.meta.hasMore, isFalse);
  });

  test('PageMeta.hasMore is true when page < totalPages', () {
    final meta = PageMeta.fromJson({'page': 1, 'perPage': 15, 'total': 30, 'totalPages': 2});

    expect(meta.hasMore, isTrue);
  });
}
