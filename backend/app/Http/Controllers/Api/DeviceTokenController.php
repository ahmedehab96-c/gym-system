<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDeviceTokenRequest;
use App\Http\Resources\DeviceTokenResource;
use App\Http\Responses\ApiResponse;
use App\Models\DeviceToken;
use App\Models\Member;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Registers/removes a push-notification device token for the staff
 * admin dashboard (Phase 24 §3) or the Flutter Member Mobile App (Phase
 * 25) — whichever kind of Sanctum token authenticated the request.
 * Always scoped to the authenticated actor's own tenant + own account —
 * never accepts a user_id/member_id/tenant_id from the request body, so
 * a token can't be registered against anyone else.
 */
class DeviceTokenController extends Controller
{
    public function store(StoreDeviceTokenRequest $request): JsonResponse
    {
        $actor = $request->user();
        $isMember = $actor instanceof Member;

        // updateOrCreate on the token itself: the same physical device
        // re-registering (e.g. after a token refresh from the OS) simply
        // reassigns ownership rather than erroring on the unique constraint.
        $token = DeviceToken::query()->updateOrCreate(
            ['token' => $request->validated('token')],
            [
                'tenant_id' => $actor->tenant_id,
                'user_id' => $isMember ? null : $actor->id,
                'member_id' => $isMember ? $actor->id : null,
                'platform' => $request->validated('platform'),
            ],
        );

        return ApiResponse::item(new DeviceTokenResource($token), 201);
    }

    public function destroy(Request $request, DeviceToken $device_token): JsonResponse
    {
        $actor = $request->user();
        $owns = $actor instanceof Member
            ? $device_token->member_id === $actor->id
            : $device_token->user_id === $actor->id;

        if (! $owns) {
            return ApiResponse::error('You can only remove your own device tokens.', [], 403);
        }

        $device_token->delete();

        return ApiResponse::message('Device token removed.');
    }
}
