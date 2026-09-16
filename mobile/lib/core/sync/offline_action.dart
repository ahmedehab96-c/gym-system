/// One safe, queueable mutation performed while offline (Phase 27 §5).
/// Only actions that are naturally idempotent server-side are ever
/// queued — see each `kind`'s repository for why it's safe to retry
/// blindly on reconnect without a duplicate-record risk:
///
/// - `classBook` / `classCancel`: Member\ClassController::book()/cancel()
///   both guard against duplicates server-side (an existing booking or a
///   missing one is a clean no-op/422, never a duplicate row).
/// - `notificationMarkRead` / `notificationMarkAllRead`: marking an
///   already-read notification read again is a harmless no-op.
///
/// Financial/payment actions are never represented here — the Payments
/// feature has no offline write path at all (Phase 27 §5: "Do NOT
/// blindly queue financial/payment operations").
enum OfflineActionKind { classBook, classCancel, notificationMarkRead, notificationMarkAllRead }

class OfflineAction {
  const OfflineAction({
    required this.id,
    required this.kind,
    required this.payload,
    required this.createdAt,
    this.attempts = 0,
  });

  factory OfflineAction.fromJson(Map<String, dynamic> json) => OfflineAction(
        id: json['id'] as String,
        kind: OfflineActionKind.values.byName(json['kind'] as String),
        payload: Map<String, dynamic>.from(json['payload'] as Map),
        createdAt: DateTime.parse(json['createdAt'] as String),
        attempts: json['attempts'] as int? ?? 0,
      );

  /// Idempotency key — generated once when the action is first queued so
  /// a retried sync pass (or a duplicate enqueue) can never replay the
  /// same intent twice as two separate records.
  final String id;
  final OfflineActionKind kind;
  final Map<String, dynamic> payload;
  final DateTime createdAt;
  final int attempts;

  Map<String, dynamic> toJson() => {
        'id': id,
        'kind': kind.name,
        'payload': payload,
        'createdAt': createdAt.toIso8601String(),
        'attempts': attempts,
      };

  OfflineAction bumpAttempts() => OfflineAction(id: id, kind: kind, payload: payload, createdAt: createdAt, attempts: attempts + 1);
}
