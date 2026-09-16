<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSubscriptionPlanRequest;
use App\Http\Requests\UpdateSubscriptionPlanRequest;
use App\Http\Resources\SubscriptionPlanResource;
use App\Http\Responses\ApiResponse;
use App\Models\SubscriptionPlan;
use Illuminate\Http\JsonResponse;

/**
 * Platform-level management of the SaaS's own pricing catalog — gated by
 * the `platform.admin` middleware (routes/api.php), never by the
 * tenant/role/permission machinery every other controller uses. No
 * Super Admin UI calls this yet (see Phase 19 constraints); it exists so
 * the backend has a real place to manage plans from once one does.
 */
class PlatformSubscriptionPlanController extends Controller
{
    public function index(): JsonResponse
    {
        $plans = SubscriptionPlan::query()->orderBy('sort_order')->get();

        return ApiResponse::item(SubscriptionPlanResource::collection($plans));
    }

    public function store(StoreSubscriptionPlanRequest $request): JsonResponse
    {
        $plan = SubscriptionPlan::create($request->validated());

        return ApiResponse::item(new SubscriptionPlanResource($plan), 201);
    }

    public function show(SubscriptionPlan $subscriptionPlan): JsonResponse
    {
        return ApiResponse::item(new SubscriptionPlanResource($subscriptionPlan));
    }

    public function update(UpdateSubscriptionPlanRequest $request, SubscriptionPlan $subscriptionPlan): JsonResponse
    {
        $subscriptionPlan->update($request->validated());

        return ApiResponse::item(new SubscriptionPlanResource($subscriptionPlan));
    }

    public function destroy(SubscriptionPlan $subscriptionPlan): JsonResponse
    {
        if ($subscriptionPlan->subscriptions()->exists()) {
            return ApiResponse::error('This plan has active tenant subscriptions and cannot be deleted.', [], 422);
        }

        $subscriptionPlan->delete();

        return ApiResponse::message('Subscription plan deleted successfully.');
    }
}
