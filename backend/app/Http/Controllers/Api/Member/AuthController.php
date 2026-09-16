<?php

namespace App\Http\Controllers\Api\Member;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\MemberResource;
use App\Http\Responses\ApiResponse;
use App\Models\Member;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Authenticates a gym Member (not staff — see App\Http\Controllers\Api\AuthController
 * for that) for the Flutter Member Mobile App (Phase 25). Issues its own
 * Sanctum token, gated everywhere else in member/ by EnsureMemberToken so
 * a member's token can never reach a staff-only endpoint.
 */
class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        /** @var Member|null $member */
        $member = Member::query()->where('email', $credentials['email'])->first();

        if (! $member || ! $member->password || ! Hash::check($credentials['password'], $member->password)) {
            throw ValidationException::withMessages([
                'email' => ['These credentials do not match our records.'],
            ]);
        }

        if ($member->status !== 'Active') {
            throw ValidationException::withMessages([
                'email' => ['This membership is not active. Please contact your gym.'],
            ]);
        }

        $token = $member->createToken('member-mobile-token')->plainTextToken;

        $member->forceFill(['last_login_at' => now()])->save();
        $member->load(['plan', 'trainer']);

        return ApiResponse::item([
            'token' => $token,
            'member' => new MemberResource($member),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return ApiResponse::message('Logged out successfully.');
    }

    public function me(Request $request): JsonResponse
    {
        $member = $request->user()->load(['plan', 'trainer']);

        return ApiResponse::item(new MemberResource($member));
    }
}
