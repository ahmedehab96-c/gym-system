<?php

namespace App\Services\Payment\Contracts;

use App\Services\Payment\DTO\CheckoutSession;
use App\Services\Payment\DTO\CheckoutSessionStatus;
use App\Services\Payment\DTO\GatewaySubscription;
use App\Services\Payment\DTO\RefundResult;
use Illuminate\Http\Request;

/**
 * Everything App\Services\Payment needs from a payment gateway, kept
 * provider-independent the same way AIProviderContract keeps the AI
 * layer independent of a specific AI vendor (see App\Services\AI). No
 * business service (CheckoutService, RefundService, ...) depends on a
 * concrete gateway — only App\Providers\AppServiceProvider does.
 */
interface PaymentGatewayContract
{
    /**
     * Starts a provider-hosted checkout for a recurring subscription.
     * The customer never enters card details on this app's own pages —
     * they're redirected to the returned URL.
     *
     * @param  array{
     *     product_name: string, unit_amount: int, currency: string, interval: string,
     *     success_url: string, cancel_url: string, client_reference_id: string,
     *     customer_email: ?string, metadata: array<string, scalar>,
     * }  $params
     */
    public function createCheckoutSession(array $params): CheckoutSession;

    /**
     * The authoritative, server-verified status of a checkout session —
     * always re-fetched from the gateway's API, never inferred from a
     * frontend redirect.
     */
    public function retrieveCheckoutSession(string $sessionId): CheckoutSessionStatus;

    public function retrieveSubscription(string $subscriptionId): GatewaySubscription;

    /** Full refund when $amountMinorUnits is null, partial otherwise. */
    public function refund(string $paymentIntentId, ?int $amountMinorUnits = null): RefundResult;

    /** Verifies the request actually came from the gateway before any webhook data is trusted. */
    public function verifyWebhookSignature(string $payload, Request $request): bool;
}
