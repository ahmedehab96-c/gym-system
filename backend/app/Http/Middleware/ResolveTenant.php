<?php

namespace App\Http\Middleware;

use App\Support\CurrentTenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Runs immediately after auth:sanctum on every authenticated API route,
 * so App\Models\Concerns\TenantScope can filter every subsequent query to
 * the authenticated user's own tenant. A user with no tenant_id (a
 * platform-level admin, see User::isPlatformAdmin()) resolves to an
 * explicit null, which TenantScope treats as "unscoped, sees everything"
 * — see App\Support\CurrentTenant for the full state table.
 */
class ResolveTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        app(CurrentTenant::class)->set($request->user()?->tenant_id);

        return $next($request);
    }
}
