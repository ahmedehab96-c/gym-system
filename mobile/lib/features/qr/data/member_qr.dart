/// A member's QR identity (Phase 28 §1) — the `token` is a random,
/// non-guessable, non-sensitive identifier (never the member's numeric
/// id/member_id, never a password or payment detail); the backend hashes
/// it at rest and only ever returns the raw value to its own owner on
/// `/member/qr` and `/member/qr/regenerate`.
class MemberQr {
  const MemberQr({
    required this.token,
    this.issuedAt,
    this.expiresAt,
    required this.memberStatus,
    this.membershipExpiryDate,
  });

  factory MemberQr.fromJson(Map<String, dynamic> json) => MemberQr(
        token: json['token'] as String,
        issuedAt: json['issuedAt'] as String?,
        expiresAt: json['expiresAt'] as String?,
        memberStatus: json['memberStatus'] as String? ?? 'Active',
        membershipExpiryDate: json['membershipExpiryDate'] as String?,
      );

  final String token;
  final String? issuedAt;
  final String? expiresAt;
  final String memberStatus;
  final String? membershipExpiryDate;

  bool get membershipUsable => memberStatus == 'Active';

  bool get isExpired {
    if (expiresAt == null) return false;
    final parsed = DateTime.tryParse(expiresAt!);
    return parsed != null && parsed.isBefore(DateTime.now());
  }
}
