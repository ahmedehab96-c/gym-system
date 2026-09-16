<?php

namespace App\Http\Middleware;

use App\Http\Responses\ApiResponse;
use App\Services\AI\AIUsageService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks an AI request once a tenant has used its plan's ai_requests
 * quota for the current period — the exact same division of
 * responsibility as App\Http\Middleware\EnforceSubscriptionLimit:
 * App\Services\AI\AIUsageService::canUseAI() only answers yes/no, this
 * middleware is what actually stops the request (402, matching
 * EnforceSubscriptionLimit's "upgrade your plan" status code).
 */
class EnforceAIUsageLimit
{
    public function __construct(private readonly AIUsageService $usage) {}

    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $request->user()?->tenant;

        if ($tenant && ! $this->usage->canUseAI($tenant)) {
            return ApiResponse::error(
                "Your plan's AI usage limit has been reached for this billing period. Upgrade your subscription to continue.",
                [],
                402,
            );
        }

        return $next($request);
    }
}
