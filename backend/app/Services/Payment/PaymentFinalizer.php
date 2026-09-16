<?php

namespace App\Services\Payment;

use App\Models\PaymentTransaction;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPlan;
use App\Models\TenantSubscription;
use App\Services\Payment\DTO\CheckoutSessionStatus;
use App\Services\SubscriptionService;
use Illuminate\Support\Carbon;

/**
 * The single idempotent core that both the return-URL verification
 * endpoint (App\Http\Controllers\Api\Payment\CheckoutController::verify)
 * and the webhook handler (PaymentWebhookHandler) call once a gateway
 * has confirmed money actually moved. Keeping this logic in one place
 * (rather than duplicating "mark paid, activate subscription" in both
 * callers) is what makes "payment must be verified server-side" and
 * "prevent duplicate transaction processing" hold no matter which of
 * the two paths gets there first.
 */
class PaymentFinalizer
{
    public function __construct(private readonly SubscriptionService $subscriptions) {}

    /** Completes the very first checkout for a tenant (Trial/none -> a paid, gateway-billed subscription). */
    public function finalizeInitialCheckout(PaymentTransaction $transaction, CheckoutSessionStatus $status): PaymentTransaction
    {
        if ($transaction->status === 'Paid') {
            return $transaction; // already finalized by the other path (webhook vs. return-URL race)
        }

        if (! $status->isPaid()) {
            if ($status->isExpired()) {
                $transaction->update(['status' => 'Failed']);
            }

            return $transaction->fresh();
        }

        $tenant = $transaction->tenant;
        $plan = SubscriptionPlan::findOrFail($transaction->metadata['plan_id']);
        $billingCycle = $transaction->metadata['billing_cycle'];
        $periodEnd = $status->currentPeriodEnd ?? Carbon::today()->addMonth();

        $subscription = $this->subscriptions->activateFromPayment($tenant, $plan, $billingCycle, $periodEnd);
        $subscription->update([
            'gateway' => $transaction->gateway,
            'gateway_customer_id' => $status->customerId,
            'gateway_subscription_id' => $status->subscriptionId,
        ]);

        $invoice = $this->subscriptions->recordPaidInvoice($subscription, Carbon::today(), $periodEnd);

        $transaction->update([
            'tenant_subscription_id' => $subscription->id,
            'subscription_invoice_id' => $invoice->id,
            'gateway_payment_intent_id' => $status->paymentIntentId,
            'status' => 'Paid',
            'paid_at' => Carbon::now(),
        ]);

        return $transaction->fresh();
    }

    /** A subsequent, gateway-driven billing-cycle charge succeeded (Stripe's own recurring billing, not a new checkout). */
    public function finalizeRenewal(
        TenantSubscription $subscription,
        string $gatewayReference,
        ?string $paymentIntentId,
        int $amountMinorUnits,
        string $currency,
        Carbon $periodEnd,
    ): void {
        if (PaymentTransaction::where('reference', $gatewayReference)->exists()) {
            return; // already recorded — duplicate webhook delivery
        }

        $subscription = $this->subscriptions->renewFromPayment($subscription, $periodEnd);
        $invoice = $this->subscriptions->recordPaidInvoice($subscription, Carbon::today(), $periodEnd);

        PaymentTransaction::create([
            'tenant_id' => $subscription->tenant_id,
            'tenant_subscription_id' => $subscription->id,
            'subscription_invoice_id' => $invoice->id,
            'gateway' => $subscription->gateway ?? 'stripe',
            'type' => 'Charge',
            'reference' => $gatewayReference,
            'gateway_payment_intent_id' => $paymentIntentId,
            'amount' => (int) round($amountMinorUnits / 100),
            'currency' => strtoupper($currency),
            'status' => 'Paid',
            'paid_at' => Carbon::now(),
            'metadata' => ['plan_id' => $subscription->plan_id, 'billing_cycle' => $subscription->billing_cycle],
        ]);
    }

    /** A gateway-driven renewal charge failed — the subscription lapses to Past Due, same as the existing manual path. */
    public function markRenewalFailed(TenantSubscription $subscription, string $gatewayReference, int $amountMinorUnits, string $currency): void
    {
        if (PaymentTransaction::where('reference', $gatewayReference)->exists()) {
            return;
        }

        $this->subscriptions->markPastDue($subscription);

        PaymentTransaction::create([
            'tenant_id' => $subscription->tenant_id,
            'tenant_subscription_id' => $subscription->id,
            'gateway' => $subscription->gateway ?? 'stripe',
            'type' => 'Charge',
            'reference' => $gatewayReference,
            'amount' => (int) round($amountMinorUnits / 100),
            'currency' => strtoupper($currency),
            'status' => 'Failed',
            'metadata' => ['plan_id' => $subscription->plan_id, 'billing_cycle' => $subscription->billing_cycle],
        ]);
    }

    public function cancelFromGateway(TenantSubscription $subscription): void
    {
        if ($subscription->status !== 'Cancelled') {
            $this->subscriptions->cancel($subscription);
        }
    }

    /**
     * Records a refund confirmation — shared by RefundService (a platform
     * admin triggered it through this app) and PaymentWebhookHandler (a
     * refund happened directly in the gateway's own dashboard). Whichever
     * path gets there first wins; the other is a no-op, by $refundReference
     * uniqueness.
     */
    public function recordRefund(PaymentTransaction $original, string $refundReference, int $amountMinorUnits, string $currency): PaymentTransaction
    {
        $existing = PaymentTransaction::where('reference', $refundReference)->first();
        if ($existing) {
            return $existing;
        }

        $refund = PaymentTransaction::create([
            'tenant_id' => $original->tenant_id,
            'tenant_subscription_id' => $original->tenant_subscription_id,
            'subscription_invoice_id' => $original->subscription_invoice_id,
            'gateway' => $original->gateway,
            'type' => 'Refund',
            'reference' => $refundReference,
            'related_reference' => $original->reference,
            'gateway_payment_intent_id' => $original->gateway_payment_intent_id,
            'amount' => (int) round($amountMinorUnits / 100),
            'currency' => strtoupper($currency),
            'status' => 'Paid',
            'paid_at' => Carbon::now(),
            'metadata' => [],
        ]);

        if ($original->subscription_invoice_id) {
            $invoice = SubscriptionInvoice::find($original->subscription_invoice_id);
            if ($invoice) {
                $this->subscriptions->markInvoiceRefunded($invoice);
            }
        }

        return $refund;
    }
}
