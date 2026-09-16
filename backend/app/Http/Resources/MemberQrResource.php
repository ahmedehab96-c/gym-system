<?php

namespace App\Http\Resources;

use App\Models\Member;
use App\Support\MembershipStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The ONLY place the raw QR token ever leaves the server — returned
 * exclusively to the member it belongs to, from their own self-service
 * `/member/qr` endpoints (Phase 28 §1/§7). Never used for anything
 * staff-facing; MemberResource deliberately never includes this.
 *
 * @mixin Member
 */
class MemberQrResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'token' => $this->qr_token,
            'issuedAt' => $this->qr_token_issued_at,
            'expiresAt' => $this->qr_token_expires_at,
            // The EFFECTIVE status — the same computation
            // QrAttendanceController::checkIn() enforces — not the raw
            // `status` column, which can lag behind `expiry_date` until
            // the next daily UpdateMembershipStatuses run (Phase 30
            // audit: the My QR screen was showing "Active" for a member
            // whose own check-in attempt was then rejected as Expired).
            'memberStatus' => MembershipStatus::resolveMemberStatus($this->expiry_date, $this->status),
            'membershipExpiryDate' => $this->expiry_date,
        ];
    }
}
