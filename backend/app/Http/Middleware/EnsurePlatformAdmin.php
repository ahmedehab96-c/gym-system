<?php

namespace App\Http\Middleware;

use App\Http\Responses\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates platform-level routes (managing the SaaS's own subscription
 * plans/pricing) to platform admins only — a distinct, separate
 * authorization axis from the existing tenant role/permission matrix,
 * per Phase 18 §8 / Phase 19 §9. No Super Admin UI exists yet; this is
 * only the backend gate it will eventually sit behind.
 */
class EnsurePlatformAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->isPlatformAdmin()) {
            return ApiResponse::error('This action requires platform-level access.', [], 403);
        }

        return $next($request);
    }
}
