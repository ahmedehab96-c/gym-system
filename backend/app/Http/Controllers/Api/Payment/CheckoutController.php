<?php

namespace App\Http\Controllers\Api\Payment;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateCheckoutRequest;
use App\Http\Requests\VerifyCheckoutRequest;
use App\Http\Resources\PaymentTransactionResource;
use App\Http\Resources\TenantSubscriptionResource;
use App\Http\Responses\ApiResponse;
use App\Models\SubscriptionPlan;
use App\Services\Payment\CheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Every action here operates on the authenticated user's OWN tenant
 * (never an {id} route parameter) — same tenant-isolation-by-construction
 * as TenantSubscriptionController, which this sits alongside under
 * /subscription/checkout.
 */
class CheckoutController extends Controller
{
    public function __construct(private readonly CheckoutService $checkout) {}

    public function store(CreateCheckoutRequest $request): JsonResponse
    {
        $plan = SubscriptionPlan::findOrFail($request->validated('plan_id'));

        $session = $this->checkout->createForTenant(
            $request->user()->tenant,
            $plan,
            $request->validated('billing_cycle'),
        );

        return ApiResponse::item(['checkoutUrl' => $session->url], 201);
    }

    /**
     * Called when the tenant lands back on the app after the gateway's
     * hosted checkout page — see CheckoutService::verify() for why this
     * re-checks the gateway's API rather than trusting the redirect.
     */
    public function verify(VerifyCheckoutRequest $request): JsonResponse
    {
        $transaction = $this->checkout->verify($request->user()->tenant, $request->validated('session_id'));

        // Read off the just-finalized transaction (fresh belongsTo lookup)
        // rather than SubscriptionService::current($request->user()->tenant)
        // — the latter can return a stale cached relation when the same
        // User/Tenant PHP object has already had its (previously null)
        // subscription relation cached earlier in the request lifecycle.
        $subscription = $transaction->subscription;

        return ApiResponse::item([
            'transaction' => new PaymentTransactionResource($transaction),
            'subscription' => $subscription ? new TenantSubscriptionResource($subscription->load('plan')) : null,
        ]);
    }
}
