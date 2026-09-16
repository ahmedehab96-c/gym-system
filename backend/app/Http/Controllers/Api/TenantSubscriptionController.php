<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangeSubscriptionPlanRequest;
use App\Http\Requests\StartSubscriptionRequest;
use App\Http\Resources\SubscriptionInvoiceResource;
use App\Http\Resources\SubscriptionPlanResource;
use App\Http\Resources\TenantSubscriptionResource;
use App\Http\Responses\ApiResponse;
use App\Models\SubscriptionPlan;
use App\Services\SubscriptionLimitService;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Every action here operates on the authenticated user's OWN tenant
 * (never an {id} route parameter), so there is no cross-tenant ID to
 * leak by construction — see Phase 18/19 tenant-isolation requirements.
 */
class TenantSubscriptionController extends Controller
{
    public function __construct(
        private readonly SubscriptionService $subscriptions,
        private readonly SubscriptionLimitService $limits,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $subscription = $this->subscriptions->current($request->user()->tenant);

        if (! $subscription) {
            return $this->noPlansConfiguredResponse();
        }

        return ApiResponse::item(new TenantSubscriptionResource($subscription->load('plan')));
    }

    public function plans(): JsonResponse
    {
        $plans = SubscriptionPlan::query()->where('status', 'Active')->orderBy('sort_order')->get();

        return ApiResponse::item(SubscriptionPlanResource::collection($plans));
    }

    public function usage(Request $request): JsonResponse
    {
        return ApiResponse::item($this->limits->usage($request->user()->tenant));
    }

    public function invoices(Request $request): JsonResponse
    {
        $subscription = $this->subscriptions->current($request->user()->tenant);

        if (! $subscription) {
            return $this->noPlansConfiguredResponse();
        }

        $perPage = min(max((int) $request->integer('per_page', 15), 1), 100);
        $paginator = $subscription->invoices()
            ->with('plan')
            ->orderByDesc('issue_date')
            ->paginate($perPage)
            ->appends($request->query());

        return ApiResponse::paginated(SubscriptionInvoiceResource::collection($paginator));
    }

    public function start(StartSubscriptionRequest $request): JsonResponse
    {
        $plan = SubscriptionPlan::findOrFail($request->validated('plan_id'));

        $subscription = $this->subscriptions->start(
            $request->user()->tenant,
            $plan,
            $request->validated('billing_cycle'),
        );

        return ApiResponse::item(new TenantSubscriptionResource($subscription->load('plan')), 201);
    }

    public function changePlan(ChangeSubscriptionPlanRequest $request): JsonResponse
    {
        $plan = SubscriptionPlan::findOrFail($request->validated('plan_id'));
        $subscription = $this->subscriptions->current($request->user()->tenant);

        if (! $subscription) {
            return $this->noPlansConfiguredResponse();
        }

        $subscription = $this->subscriptions->changePlan($subscription, $plan);

        return ApiResponse::item(new TenantSubscriptionResource($subscription->load('plan')));
    }

    public function cancel(Request $request): JsonResponse
    {
        $subscription = $this->subscriptions->current($request->user()->tenant);

        if (! $subscription) {
            return $this->noPlansConfiguredResponse();
        }

        if ($subscription->status === 'Cancelled') {
            return ApiResponse::error('This subscription is already cancelled.', [], 422);
        }

        $subscription = $this->subscriptions->cancel($subscription);

        return ApiResponse::item(new TenantSubscriptionResource($subscription->load('plan')));
    }

    public function reactivate(Request $request): JsonResponse
    {
        $subscription = $this->subscriptions->current($request->user()->tenant);

        if (! $subscription) {
            return $this->noPlansConfiguredResponse();
        }

        if ($subscription->status !== 'Cancelled') {
            return ApiResponse::error('Only a cancelled subscription can be reactivated.', [], 422);
        }

        $subscription = $this->subscriptions->reactivate($subscription);

        return ApiResponse::item(new TenantSubscriptionResource($subscription->load('plan')));
    }

    public function renew(Request $request): JsonResponse
    {
        $subscription = $this->subscriptions->current($request->user()->tenant);

        if (! $subscription) {
            return $this->noPlansConfiguredResponse();
        }

        if (! in_array($subscription->status, ['Active', 'Past Due', 'Expired'], true)) {
            return ApiResponse::error('Only an active, past-due, or expired subscription can be renewed.', [], 422);
        }

        $subscription = $this->subscriptions->renew($subscription);

        return ApiResponse::item(new TenantSubscriptionResource($subscription->load('plan')));
    }

    /**
     * The platform has no SubscriptionPlan configured at all yet (e.g. a
     * fresh environment before SubscriptionPlanSeeder has run) — a real
     * setup state, not a tenant-facing error, but there's nothing this
     * endpoint can meaningfully return until at least one plan exists.
     */
    private function noPlansConfiguredResponse(): JsonResponse
    {
        return ApiResponse::error('No subscription plans are configured yet.', [], 503);
    }
}
