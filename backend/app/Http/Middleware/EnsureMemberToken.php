<?php

namespace App\Http\Middleware;

use App\Http\Responses\ApiResponse;
use App\Models\Member;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates the member/ API surface (Phase 25 — the Flutter Member Mobile
 * App) to Member-issued Sanctum tokens only — the same auth:sanctum
 * guard accepts either a User's or a Member's token (both implement
 * Authenticatable), so this is what stops a staff token from reaching
 * member-only endpoints and vice versa. Mirrors EnsurePlatformAdmin's
 * role as an orthogonal actor-type gate, not a permission check.
 */
class EnsureMemberToken
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() instanceof Member) {
            return ApiResponse::error('This action requires a member account.', [], 403);
        }

        return $next($request);
    }
}
