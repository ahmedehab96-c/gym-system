<?php

namespace App\Services\Payment;

use App\Models\PaymentTransaction;
use App\Models\TenantSubscription;
use App\Services\Payment\Contracts\PaymentGatewayContract;

/**
 * Dispatches a verified (see VerifyPaymentWebhookSignature) gateway
 * webhook event to PaymentFinalizer. Only ever called from
 * App\Http\Controllers\Api\Payment\PaymentWebhookController, inside a
 * DB transaction keyed on the event's unique id — see that controller
 * and the payment_webhook_events migration for the idempotency guard.
 * This class only decides WHAT an event means; it never talks to the
 * gateway's write endpoints itself (no charges/refunds originate here).
 */
class PaymentWebhookHandler
{
    public function __construct(
        private readonly PaymentGatewayContract $gateway,
        private readonly PaymentFinalizer $finalizer,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(string $type, array $data): void
    {
        match ($type) {
            'checkout.session.completed' => $this->handleCheckoutCompleted($data),
            'invoice.payment_succeeded' => $this->handleInvoicePaymentSucceeded($data),
            'invoice.payment_failed' => $this->handleInvoicePaymentFailed($data),
            'customer.subscription.deleted' => $this->handleSubscriptionDeleted($data),
            'charge.refunded' => $this->handleChargeRefunded($data),
            default => null, // Events we don't act on are received and logged, not processed.
        };
    }

    private function handleCheckoutCompleted(array $session): void
    {
        if (($session['mode'] ?? null) !== 'subscription' || ! is_string($session['id'] ?? null)) {
            return;
        }

        $transaction = PaymentTransaction::where('gateway', 'stripe')->where('reference', $session['id'])->first();
        if (! $transaction) {
            return; // Not a session this app created (or already resolved via the return-URL path).
        }

        // Re-fetched from the API rather than trusted from the webhook
        // payload — same authoritative check the return-URL path uses.
        $status = $this->gateway->retrieveCheckoutSession($session['id']);
        $this->finalizer->finalizeInitialCheckout($transaction, $status);
    }

    private function handleInvoicePaymentSucceeded(array $invoice): void
    {
        $subscriptionId = $invoice['subscription'] ?? null;
        if (! is_string($subscriptionId)) {
            return;
        }

        $subscription = TenantSubscription::where('gateway_subscription_id', $subscriptionId)->first();
        if (! $subscription) {
            return; // Not the subscription's very first invoice — that one is handled by checkout.session.completed.
        }

        $gatewaySubscription = $this->gateway->retrieveSubscription($subscriptionId);

        $this->finalizer->finalizeRenewal(
            $subscription,
            $invoice['id'],
            is_string($invoice['payment_intent'] ?? null) ? $invoice['payment_intent'] : null,
            (int) ($invoice['amount_paid'] ?? 0),
            $invoice['currency'] ?? 'usd',
            $gatewaySubscription->currentPeriodEnd,
        );
    }

    private function handleInvoicePaymentFailed(array $invoice): void
    {
        $subscriptionId = $invoice['subscription'] ?? null;
        if (! is_string($subscriptionId)) {
            return;
        }

        $subscription = TenantSubscription::where('gateway_subscription_id', $subscriptionId)->first();
        if (! $subscription) {
            return;
        }

        $this->finalizer->markRenewalFailed(
            $subscription,
            $invoice['id'],
            (int) ($invoice['amount_due'] ?? 0),
            $invoice['currency'] ?? 'usd',
        );
    }

    private function handleSubscriptionDeleted(array $subscriptionData): void
    {
        $subscriptionId = $subscriptionData['id'] ?? null;
        if (! is_string($subscriptionId)) {
            return;
        }

        $subscription = TenantSubscription::where('gateway_subscription_id', $subscriptionId)->first();
        if ($subscription) {
            $this->finalizer->cancelFromGateway($subscription);
        }
    }

    private function handleChargeRefunded(array $charge): void
    {
        $paymentIntentId = $charge['payment_intent'] ?? null;
        $refundId = $charge['refunds']['data'][0]['id'] ?? null;

        if (! is_string($paymentIntentId) || ! is_string($refundId)) {
            return;
        }

        $original = PaymentTransaction::where('gateway_payment_intent_id', $paymentIntentId)
            ->where('type', 'Charge')
            ->first();

        if (! $original) {
            return;
        }

        $this->finalizer->recordRefund(
            $original,
            $refundId,
            (int) ($charge['amount_refunded'] ?? 0),
            $charge['currency'] ?? $original->currency,
        );
    }
}
