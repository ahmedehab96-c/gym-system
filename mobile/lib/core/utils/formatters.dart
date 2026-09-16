import 'package:intl/intl.dart';

class Formatters {
  const Formatters._();

  static final _dateFormat = DateFormat('MMM d, yyyy');
  static final _dateTimeFormat = DateFormat('MMM d, yyyy · h:mm a');
  static final _currencyFormat = NumberFormat.currency(symbol: 'EGP ', decimalDigits: 0);

  /// Accepts either a date-only string ("2026-01-05") or a full ISO
  /// timestamp; returns the raw input unchanged if it isn't parseable
  /// rather than throwing, since API data should never crash the UI.
  static String date(String? value) {
    if (value == null || value.isEmpty) return '—';
    final parsed = DateTime.tryParse(value);
    if (parsed == null) return value;
    return _dateFormat.format(parsed);
  }

  static String dateTime(String? value) {
    if (value == null || value.isEmpty) return '—';
    final parsed = DateTime.tryParse(value);
    if (parsed == null) return value;
    return _dateTimeFormat.format(parsed.toLocal());
  }

  static String currency(num? value) {
    if (value == null) return '—';
    return _currencyFormat.format(value);
  }

  static String timeAgo(String? value) {
    if (value == null || value.isEmpty) return '—';
    final parsed = DateTime.tryParse(value);
    if (parsed == null) return value;
    final diff = DateTime.now().difference(parsed.toLocal());

    if (diff.inSeconds < 60) return 'just now';
    if (diff.inMinutes < 60) return '${diff.inMinutes}m ago';
    if (diff.inHours < 24) return '${diff.inHours}h ago';
    if (diff.inDays < 7) return '${diff.inDays}d ago';
    return date(value);
  }

  static int? daysUntil(String? isoDate) {
    if (isoDate == null || isoDate.isEmpty) return null;
    final parsed = DateTime.tryParse(isoDate);
    if (parsed == null) return null;
    final today = DateTime.now();
    final target = DateTime(parsed.year, parsed.month, parsed.day);
    final now = DateTime(today.year, today.month, today.day);
    return target.difference(now).inDays;
  }
}
