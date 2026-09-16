import 'package:flutter/material.dart';

class StatusBadge extends StatelessWidget {
  const StatusBadge({super.key, required this.status});

  final String status;

  static const Map<String, Color> _positive = {
    'Active': Color(0xFF22C55E),
    'Paid': Color(0xFF22C55E),
    'Scheduled': Color(0xFF5B8DEF),
    'Sent': Color(0xFF22C55E),
    'Checked In': Color(0xFF5B8DEF),
    'Checked Out': Color(0xFF6B7280),
  };

  static const Map<String, Color> _warning = {
    'Expiring Soon': Color(0xFFD4A72F),
    'Pending': Color(0xFFD4A72F),
    'Full': Color(0xFFD4A72F),
  };

  static const Map<String, Color> _negative = {
    'Expired': Color(0xFFE0263C),
    'Suspended': Color(0xFFE0263C),
    'Failed': Color(0xFFE0263C),
    'Cancelled': Color(0xFFE0263C),
    'Inactive': Color(0xFF6B7280),
    'Overdue': Color(0xFFE0263C),
    'Unpaid': Color(0xFFD4A72F),
  };

  Color _colorFor(String status) => _positive[status] ?? _warning[status] ?? _negative[status] ?? const Color(0xFF6B7280);

  @override
  Widget build(BuildContext context) {
    final color = _colorFor(status);
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(color: color.withValues(alpha: 0.12), borderRadius: BorderRadius.circular(20)),
      child: Text(status, style: TextStyle(color: color, fontSize: 12, fontWeight: FontWeight.w600)),
    );
  }
}
