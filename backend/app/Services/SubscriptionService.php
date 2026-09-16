<?php

namespace App\Services;

use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Centralizes tenant subscription lifecycle transitions (start/renew/
 * change-plan/cancel/reactivate), the same way MembershipService
 * centralizes a gym member's own membership lifecycle. No real payment
 * gateway is wired in here yet — see the class-level note on each method
 * that would eventually call one.
 */
class SubscriptionService
{
    /**
     * The subscription every tenant has exactly one of. Creates a Trial
     * subscription on the platform's cheapest plan the first time a
     * tenant is looked up without one yet — unless the plan catalog
     * itself is empty (no SubscriptionPlan exists), in which case this
     * returns null rather than throwing. That's a real, expected state
     * before the platform's plans are ever seeded (e.g. a fresh test
     * database), and the SaaS billing layer being unconfigured must
     * never block core gym functionality — see SubscriptionLimitService,
     * which treats a null subscription as "no limits to enforce yet".
     */
    public function current(Tenant $tenant): ?TenantSubscription
    {
        if ($tenant->subscription) {
            return $tenant->subscription;
        }

        $plan = $this->defaultTrialPlan();

        return $plan ? $this->startTrial($tenant, $plan) : null;
    }

    public function startTrial(Tenant $tenant, SubscriptionPlan $plan): TenantSubscription
    {
        $now = Carbon::today();

        return TenantSubscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'Trial',
            'billing_cycle' => 'Monthly',
            'price' => 0,
            'trial_starts_at' => $now->toDateString(),
            'trial_ends_at' => $now->copy()->addDays($plan->trial_days)->toDateString(),
            'next_billing_at' => null,
        ]);
    }

    /**
     * Starts a paid subscription directly (skipping/ending any trial).
     * In a future phase, this is where a Stripe (or similar) checkout
     * would be created before the subscription is actually marked Active
     * — for now it activates immediately and records a Pending billing
     * history entry, since no payment processing exists yet.
     */
    public function start(Tenant $tenant, SubscriptionPlan $plan, string $billingCycle): TenantSubscription
    {
        // Resolved directly (not via current()) so starting a subscription
        // never depends on a default trial plan existing to seed one —
        // the plan to start on is already known and validated here.
        $now = Carbon::today();
        $periodEnd = $billingCycle === 'Yearly' ? $now->copy()->addYear() : $now->copy()->addMonth();

        $subscription = $this->activate($tenant, $plan, $billingCycle, $now, $periodEnd);

        $this->recordInvoice($subscription, $now, $periodEnd);

        return $subscription->fresh();
    }

    /**
     * Activates (or reactivates) a subscription because a real payment
     * gateway has already verified the charge server-side — see
     * App\Services\Payment\PaymentFinalizer, which is the only caller.
     * Unlike start(), this does not record a new Pending invoice: the
     * invoice for this payment is recorded (already Paid) separately via
     * recordPaidInvoice(), since no invoice should ever sit Pending for
     * a payment that has already succeeded.
     */
    public function activateFromPayment(Tenant $tenant, SubscriptionPlan $plan, string $billingCycle, Carbon $periodEnd): TenantSubscription
    {
        return $this->activate($tenant, $plan, $billingCycle, Carbon::today(), $periodEnd)->fresh();
    }

    public function renew(TenantSubscription $subscription): TenantSubscription
    {
        $periodEnd = $this->nextPeriodEnd($subscription);
        $this->applyRenewal($subscription, $periodEnd);

        $this->recordInvoice($subscription->fresh(), Carbon::today(), $periodEnd);

        return $subscription->fresh();
    }

    /**
     * The webhook-driven counterpart to renew() — the gateway has already
     * collected payment for this cycle (see App\Services\Payment\PaymentFinalizer),
     * so, like activateFromPayment(), this doesn't record a new Pending
     * invoice itself.
     */
    public function renewFromPayment(TenantSubscription $subscription, Carbon $periodEnd): TenantSubscription
    {
        $this->applyRenewal($subscription, $periodEnd);

        return $subscription->fresh();
    }

    public function changePlan(TenantSubscription $subscription, SubscriptionPlan $plan): TenantSubscription
    {
        $price = $subscription->billing_cycle === 'Yearly' ? $plan->yearly_price : $plan->monthly_price;

        $subscription->update([
            'plan_id' => $plan->id,
            'price' => $price,
        ]);

        return $subscription->fresh();
    }

    public function cancel(TenantSubscription $subscription): TenantSubscription
    {
        $subscription->update([
            'status' => 'Cancelled',
            'cancelled_at' => Carbon::today()->toDateString(),
        ]);

        return $subscription->fresh();
    }

    /**
     * Only meaningful from Cancelled (before it lapses into Expired) —
     * once Expired, a tenant should start a fresh subscription instead.
     */
    public function reactivate(TenantSubscription $subscription): TenantSubscription
    {
        $now = Carbon::today();
        $periodEnd = $subscription->billing_cycle === 'Yearly' ? $now->copy()->addYear() : $now->copy()->addMonth();

        $subscription->update([
            'status' => 'Active',
            'cancelled_at' => null,
            'expires_at' => null,
            'next_billing_at' => $periodEnd->toDateString(),
        ]);

        return $subscription->fresh();
    }

    public function markPastDue(TenantSubscription $subscription): TenantSubscription
    {
        $subscription->update(['status' => 'Past Due']);

        return $subscription->fresh();
    }

    public function expire(TenantSubscription $subscription): TenantSubscription
    {
        $subscription->update([
            'status' => 'Expired',
            'expires_at' => Carbon::today()->toDateString(),
        ]);

        return $subscription->fresh();
    }

    /**
     * Records a billing-cycle invoice as Pending — the default for
     * start()/renew(), which don't yet know whether payment will
     * succeed. See recordPaidInvoice() for the payment-verified path.
     */
    private function recordInvoice(TenantSubscription $subscription, Carbon $periodStart, Carbon $periodEnd): SubscriptionInvoice
    {
        return SubscriptionInvoice::create([
            'tenant_id' => $subscription->tenant_id,
            'tenant_subscription_id' => $subscription->id,
            'plan_id' => $subscription->plan_id,
            'invoice_number' => 'SUB-'.strtoupper(Str::random(8)),
            'amount' => $subscription->price,
            'currency' => $subscription->tenant?->currency ?? 'USD',
            'billing_period_start' => $periodStart->toDateString(),
            'billing_period_end' => $periodEnd->toDateString(),
            'status' => 'Pending',
            'issue_date' => Carbon::today()->toDateString(),
            'due_date' => Carbon::today()->addDays(7)->toDateString(),
        ]);
    }

    /**
     * Records an invoice that is already Paid — used by
     * App\Services\Payment\PaymentFinalizer once the gateway has
     * confirmed a charge succeeded, so a real payment is never left
     * sitting behind a Pending invoice.
     */
    public function recordPaidInvoice(TenantSubscription $subscription, Carbon $periodStart, Carbon $periodEnd, ?Carbon $paidDate = null): SubscriptionInvoice
    {
        $invoice = $this->recordInvoice($subscription, $periodStart, $periodEnd);

        return $this->markInvoicePaid($invoice, $paidDate);
    }

    public function markInvoicePaid(SubscriptionInvoice $invoice, ?Carbon $paidDate = null): SubscriptionInvoice
    {
        $invoice->update(['status' => 'Paid', 'paid_date' => ($paidDate ?? Carbon::today())->toDateString()]);

        return $invoice->fresh();
    }

    public function markInvoiceFailed(SubscriptionInvoice $invoice): SubscriptionInvoice
    {
        $invoice->update(['status' => 'Failed']);

        return $invoice->fresh();
    }

    public function markInvoiceRefunded(SubscriptionInvoice $invoice): SubscriptionInvoice
    {
        $invoice->update(['status' => 'Refunded']);

        return $invoice->fresh();
    }

    /** Shared by start() and activateFromPayment() — see each method's docblock. */
    private function activate(Tenant $tenant, SubscriptionPlan $plan, string $billingCycle, Carbon $startedAt, Carbon $periodEnd): TenantSubscription
    {
        $price = $billingCycle === 'Yearly' ? $plan->yearly_price : $plan->monthly_price;

        return TenantSubscription::query()->updateOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'plan_id' => $plan->id,
                'status' => 'Active',
                'billing_cycle' => $billingCycle,
                'price' => $price,
                'started_at' => $startedAt->toDateString(),
                'next_billing_at' => $periodEnd->toDateString(),
                'cancelled_at' => null,
                'expires_at' => null,
            ],
        );
    }

    /** Shared by renew() and renewFromPayment() — see each method's docblock. */
    private function applyRenewal(TenantSubscription $subscription, Carbon $periodEnd): void
    {
        $plan = $subscription->plan;

        $subscription->update([
            'status' => 'Active',
            'price' => $subscription->billing_cycle === 'Yearly' ? $plan->yearly_price : $plan->monthly_price,
            'next_billing_at' => $periodEnd->toDateString(),
            'expires_at' => null,
        ]);
    }

    private function nextPeriodEnd(TenantSubscription $subscription): Carbon
    {
        $base = $subscription->next_billing_at && Carbon::parse($subscription->next_billing_at)->isFuture()
            ? Carbon::parse($subscription->next_billing_at)
            : Carbon::today();

        return $subscription->billing_cycle === 'Yearly' ? $base->copy()->addYear() : $base->copy()->addMonth();
    }

    private function defaultTrialPlan(): ?SubscriptionPlan
    {
        return SubscriptionPlan::query()->where('status', 'Active')->orderBy('sort_order')->first();
    }
}
