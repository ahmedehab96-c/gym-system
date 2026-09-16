<?php

namespace App\Http\Middleware;

use App\Http\Responses\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authorizes a request against the role_permissions matrix for a given
 * module. The required ability is either given explicitly (for action
 * routes like renew/suspend that don't map cleanly to a CRUD verb) or
 * derived from the HTTP verb: GET/HEAD -> view, POST -> create,
 * PUT/PATCH -> edit, DELETE -> delete.
 */
class CheckPermission
{
    public function handle(Request $request, Closure $next, string $module, ?string $ability = null): Response
    {
        $user = $request->user();

        $ability ??= match ($request->method()) {
            'GET', 'HEAD' => 'view',
            'POST' => 'create',
            'PUT', 'PATCH' => 'edit',
            'DELETE' => 'delete',
            default => null,
        };

        if (! $user || $ability === null || ! $user->hasPermission($module, $ability)) {
            return ApiResponse::error('You do not have permission to perform this action.', [], 403);
        }

        return $next($request);
    }
}
