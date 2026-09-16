<?php

namespace Tests\Fakes;

use App\Services\Payment\Contracts\PaymentGatewayContract;
use App\Services\Payment\DTO\CheckoutSession;
use App\Services\Payment\DTO\CheckoutSessionStatus;
use App\Services\Payment\DTO\GatewaySubscription;
use App\Services\Payment\DTO\RefundResult;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Bound over PaymentGatewayContract in payment feature tests so the
 * suite never makes a real network call to Stripe (Phase 23 §10) —
 * mirrors Tests\Fakes\FakeAIProvider. Signature verification always
 * succeeds here on purpose (tests using this fake are about
 * App\Services\Payment business logic, not the HMAC algorithm itself —
 * see Tests\Feature\Payment\StripeWebhookSignatureTest for that).
 */
class FakePaymentGateway implements PaymentGatewayContract
{
    /** @var array<int, array{method: string, args: array}> */
    public array $calls = [];

    /** @var array<string, CheckoutSessionStatus> */
    public array $checkoutSessionStatuses = [];

    /** @var array<string, GatewaySubscription> */
    public array $gatewaySubscriptions = [];

    public ?RefundResult $refundResult = null;

    public bool $verifySignatureResult = true;

    public ?\Throwable $throwOnCreateCheckout = null;

    public function createCheckoutSession(array $params): CheckoutSession
    {
        $this->calls[] = ['method' => 'createCheckoutSession', 'args' => $params];

        if ($this->throwOnCreateCheckout) {
            throw $this->throwOnCreateCheckout;
        }

        $id = 'cs_test_'.Str::random(16);

        return new CheckoutSession(id: $id, url: "https://checkout.stripe.test/pay/{$id}");
    }

    public function retrieveCheckoutSession(string $sessionId): CheckoutSessionStatus
    {
        $this->calls[] = ['method' => 'retrieveCheckoutSession', 'args' => ['session_id' => $sessionId]];

        return $this->checkoutSessionStatuses[$sessionId] ?? new CheckoutSessionStatus(
            id: $sessionId,
            status: 'open',
            paymentStatus: 'unpaid',
            subscriptionId: null,
            customerId: null,
            paymentIntentId: null,
            currentPeriodEnd: null,
        );
    }

    public function retrieveSubscription(string $subscriptionId): GatewaySubscription
    {
        $this->calls[] = ['method' => 'retrieveSubscription', 'args' => ['subscription_id' => $subscriptionId]];

        return $this->gatewaySubscriptions[$subscriptionId] ?? new GatewaySubscription(
            id: $subscriptionId,
            status: 'active',
            currentPeriodEnd: Carbon::today()->addMonth(),
        );
    }

    public function refund(string $paymentIntentId, ?int $amountMinorUnits = null): RefundResult
    {
        $this->calls[] = ['method' => 'refund', 'args' => ['payment_intent_id' => $paymentIntentId, 'amount' => $amountMinorUnits]];

        return $this->refundResult ?? new RefundResult(
            reference: 're_test_'.Str::random(16),
            status: 'succeeded',
            amountMinorUnits: $amountMinorUnits ?? 0,
            currency: 'usd',
        );
    }

    public function verifyWebhookSignature(string $payload, Request $request): bool
    {
        return $this->verifySignatureResult;
    }

    /** Test helper: stage what retrieveCheckoutSession() returns for a given session id. */
    public function stageCheckoutSession(string $sessionId, CheckoutSessionStatus $status): void
    {
        $this->checkoutSessionStatuses[$sessionId] = $status;
    }

    /** Test helper: stage what retrieveSubscription() returns for a given gateway subscription id. */
    public function stageSubscription(string $subscriptionId, GatewaySubscription $subscription): void
    {
        $this->gatewaySubscriptions[$subscriptionId] = $subscription;
    }
}
