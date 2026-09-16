<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\QrTokenRequest;
use App\Http\Resources\AttendanceResource;
use App\Http\Resources\MemberResource;
use App\Http\Responses\ApiResponse;
use App\Models\AttendanceRecord;
use App\Models\Member;
use App\Services\NotificationService;
use App\Support\AttendanceDuration;
use App\Support\MembershipStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

/**
 * Front-desk QR scanning (Phase 28 §3/§6) — a scan resolves straight to a
 * member (via Member::findByQrTokenHash(), which is tenant-scoped like
 * every other query on that model) and both validates and checks in/out
 * in one round trip, matching the SCAN -> IDENTIFY -> VALIDATE ->
 * CHECK-IN -> SUCCESS flow the reception UI presents as instantaneous.
 * Every validation happens server-side against real data — the token is
 * the only thing the client supplies (§7 "do not trust frontend
 * validation").
 */
class QrAttendanceController extends Controller
{
    public function checkIn(QrTokenRequest $request, NotificationService $notifications): JsonResponse
    {
        $member = Member::findByQrTokenHash($request->validated('token'));

        if (! $member) {
            return ApiResponse::error('Invalid QR code.', [], 404);
        }

        if ($member->qr_token_expires_at && $member->qr_token_expires_at->isPast()) {
            return ApiResponse::error('This QR code has expired. Ask the member to open their app to refresh it.', [], 422);
        }

        $effectiveStatus = MembershipStatus::resolveMemberStatus(
            $member->expiry_date?->toDateString(),
            $member->status
        );

        if ($effectiveStatus !== 'Active') {
            return ApiResponse::error("This member's membership is {$effectiveStatus} and cannot check in.", [], 422);
        }

        $active = AttendanceRecord::query()->where('member_id', $member->id)->whereNull('check_out')->first();

        // A repeated/duplicate scan of an already-checked-in member is
        // treated as an idempotent success, not an error — the front desk
        // sees "already checked in" rather than a scary failure for what
        // is very often just the same physical scan firing twice (Phase
        // 28 §5 "idempotency protection for repeated scans").
        if ($active) {
            $active->load('member');

            return ApiResponse::item([
                'member' => new MemberResource($member),
                'attendance' => new AttendanceResource($active),
                'duplicate' => true,
            ]);
        }

        $record = AttendanceRecord::create([
            'member_id' => $member->id,
            'date' => Carbon::today()->toDateString(),
            'check_in' => Carbon::now()->format('G:i'),
            'method' => 'QR Code',
        ]);
        $record->load('member');

        $notifications->checkedIn($record);

        return ApiResponse::item([
            'member' => new MemberResource($member),
            'attendance' => new AttendanceResource($record),
            'duplicate' => false,
        ], 201);
    }

    public function checkOut(QrTokenRequest $request): JsonResponse
    {
        $member = Member::findByQrTokenHash($request->validated('token'));

        if (! $member) {
            return ApiResponse::error('Invalid QR code.', [], 404);
        }

        $active = AttendanceRecord::query()->where('member_id', $member->id)->whereNull('check_out')->first();

        if (! $active) {
            return ApiResponse::error('This member does not have an active check-in.', [], 422);
        }

        $checkOut = Carbon::now();
        $active->update([
            'check_out' => $checkOut->format('G:i'),
            'duration' => AttendanceDuration::format(AttendanceDuration::minutesBetween($active->check_in, $checkOut->format('G:i'))),
        ]);
        $active->load('member');

        return ApiResponse::item([
            'member' => new MemberResource($member),
            'attendance' => new AttendanceResource($active),
        ]);
    }
}
