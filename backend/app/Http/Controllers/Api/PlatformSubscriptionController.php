<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TenantSubscriptionResource;
use App\Http\Responses\ApiResponse;
use App\Models\TenantSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Read-only, cross-tenant view of every gym's SaaS subscription — the
 * platform admin's Subscriptions screen. Managing a single tenant's own
 * subscription lifecycle (start/renew/cancel/...) stays on
 * TenantSubscriptionController, scoped to the caller's own tenant; this
 * controller never mutates anything.
 */
class PlatformSubscriptionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = TenantSubscription::query()->with(['tenant', 'plan']);

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($planId = $request->integer('plan_id')) {
            $query->where('plan_id', $planId);
        }

        if ($tenantId = $request->integer('tenant_id')) {
            $query->where('tenant_id', $tenantId);
        }

        if ($billingCycle = $request->string('billing_cycle')->toString()) {
            $query->where('billing_cycle', $billingCycle);
        }

        if ($search = $request->string('search')->trim()->toString()) {
            $query->whereHas('tenant', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")->orWhere('slug', 'like', "%{$search}%");
            });
        }

        $query->orderByDesc('created_at');

        $perPage = min(max((int) $request->integer('per_page', 15), 1), 100);
        $paginator = $query->paginate($perPage)->appends($request->query());

        return ApiResponse::paginated(TenantSubscriptionResource::collection($paginator));
    }
}
