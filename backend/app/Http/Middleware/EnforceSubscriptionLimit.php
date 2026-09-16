<?php

namespace App\Http\Middleware;

use App\Http\Responses\ApiResponse;
use App\Services\SubscriptionLimitService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks a create action once a tenant's plan limit for that resource is
 * reached — declared on routes the same way `permission:Module,ability`
 * already is, so the limit check lives here and in
 * SubscriptionLimitService only, not inside each controller's store().
 *
 * Attached to a whole apiResource (index/show/store/update/destroy) since
 * Laravel's per-action resource middleware (middlewareFor) doesn't
 * compose cleanly with route caching/registration here; it only actually
 * enforces on the POST (store) request, so listing/editing/deleting are
 * unaffected.
 */
class EnforceSubscriptionLimit
{
    public function __construct(private readonly SubscriptionLimitService $limits) {}

    public function handle(Request $request, Closure $next, string $resource): Response
    {
        if (! $request->isMethod('POST')) {
            return $next($request);
        }

        $tenant = $request->user()?->tenant;

        // No tenant (a platform admin, or an unauthenticated/guest edge
        // case auth:sanctum would already have rejected) -> not a plan
        // limit this middleware is responsible for enforcing.
        if ($tenant && ! $this->limits->canCreate($tenant, $resource)) {
            return ApiResponse::error(
                "Your plan's limit for {$resource} has been reached. Upgrade your subscription to add more.",
                [],
                402,
            );
        }

        return $next($request);
    }
}
