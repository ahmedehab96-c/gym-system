<?php

namespace App\Http\Controllers\Api\Member;

use App\Http\Controllers\Controller;
use App\Http\Resources\MemberQrResource;
use App\Http\Responses\ApiResponse;
use App\Models\Member;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Self-service member QR identity (Phase 28 §1) — a member can only ever
 * fetch or regenerate their OWN token; nothing here ever takes a member
 * id from the request, it's always $request->user().
 */
class QrController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        /** @var Member $member */
        $member = $request->user();

        if (! $member->qr_token || ($member->qr_token_expires_at && $member->qr_token_expires_at->isPast())) {
            $member->issueQrToken();
        }

        return ApiResponse::item(new MemberQrResource($member));
    }

    public function regenerate(Request $request): JsonResponse
    {
        /** @var Member $member */
        $member = $request->user();

        $member->issueQrToken();

        return ApiResponse::item(new MemberQrResource($member));
    }
}
