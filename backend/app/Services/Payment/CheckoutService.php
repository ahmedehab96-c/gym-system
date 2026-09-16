<?php

namespace App\Services\Payment;

use App\Models\PaymentTransaction;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Services\Payment\Contracts\PaymentGatewayContract;
use App\Services\Payment\DTO\CheckoutSession;
use App\Services\SubscriptionService;

/**
 * Starts and verifies a tenant's SaaS-plan checkout. Deliberately does
 * NOT touch TenantSubscription at all when creating a session — a
 * subscription only ever becomes Active once PaymentFinalizer has been
 * handed a server-verified "paid" status (via verify() below or the
 * webhook), never from the act of creating a checkout session itself.
 */
class CheckoutService
{
    public function __construct(
        private readonly PaymentGatewayContract $gateway,
        private readonly PaymentFinalizer $finalizer,
        private readonly SubscriptionService $subscriptions,
    ) {}

    public function createForTenant(Tenant $tenant, SubscriptionPlan $plan, string $billingCycle): CheckoutSession
    {
        $price = $billingCycle === 'Yearly' ? $plan->yearly_price : $plan->monthly_price;
        $currentSubscription = $this->subscriptions->current($tenant);

        $metadata = [
            'tenant_id' => (string) $tenant->id,
            'plan_id' => (string) $plan->id,
            'billing_cycle' => $billingCycle,
        ];

        $session = $this->gateway->createCheckoutSession([
            'product_name' => "{$plan->name} plan ({$billingCycle})",
            'unit_amount' => $price * 100,
            'currency' => config('payment.currency', 'usd'),
            'interval' => $billingCycle === 'Yearly' ? 'year' : 'month',
            'success_url' => config('payment.checkout_success_url'),
            'cancel_url' => config('payment.checkout_cancel_url'),
            'client_reference_id' => (string) $tenant->id,
            'customer_email' => $tenant->email,
            'metadata' => $metadata,
        ]);

        PaymentTransaction::create([
            'tenant_id' => $tenant->id,
            'tenant_subscription_id' => $currentSubscription?->id,
            'gateway' => 'stripe',
            'type' => 'Charge',
            'reference' => $session->id,
            'amount' => $price,
            'currency' => strtoupper(config('payment.currency', 'usd')),
            'status' => 'Pending',
            'metadata' => array_merge($metadata, ['plan_name' => $plan->name]),
        ]);

        return $session;
    }

    /**
     * Called when the tenant is redirected back from the gateway's
     * hosted checkout page. The redirect itself proves nothing — this
     * re-fetches the session directly from the gateway's API and only
     * finalizes if that server-side status says paid. Scoped to the
     * caller's own tenant so a session id can't be probed cross-tenant.
     */
    public function verify(Tenant $tenant, string $sessionId): PaymentTransaction
    {
        $transaction = PaymentTransaction::where('tenant_id', $tenant->id)
            ->where('reference', $sessionId)
            ->firstOrFail();

        if ($transaction->status === 'Paid') {
            return $transaction;
        }

        $status = $this->gateway->retrieveCheckoutSession($sessionId);

        return $this->finalizer->finalizeInitialCheckout($transaction, $status);
    }
}
