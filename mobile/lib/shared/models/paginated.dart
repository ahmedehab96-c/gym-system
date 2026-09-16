class PageMeta {
  const PageMeta({required this.page, required this.perPage, required this.total, required this.totalPages});

  factory PageMeta.fromJson(Map<String, dynamic> json) => PageMeta(
        page: (json['page'] as num?)?.toInt() ?? 1,
        perPage: (json['perPage'] as num?)?.toInt() ?? 15,
        total: (json['total'] as num?)?.toInt() ?? 0,
        totalPages: (json['totalPages'] as num?)?.toInt() ?? 1,
      );

  final int page;
  final int perPage;
  final int total;
  final int totalPages;

  bool get hasMore => page < totalPages;
}

class Paginated<T> {
  const Paginated({required this.items, required this.meta});

  factory Paginated.fromJson(Map<String, dynamic> json, T Function(Map<String, dynamic>) fromJson) => Paginated(
        items: (json['data'] as List? ?? []).map((e) => fromJson(e as Map<String, dynamic>)).toList(),
        meta: PageMeta.fromJson(json['meta'] as Map<String, dynamic>? ?? const {}),
      );

  final List<T> items;
  final PageMeta meta;
}
