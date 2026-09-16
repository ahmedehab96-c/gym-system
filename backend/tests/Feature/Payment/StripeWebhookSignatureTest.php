<?php

namespace Tests\Feature\Payment;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Exercises the REAL StripeProvider signature algorithm end-to-end
 * (no fake gateway bound) — Tests\Feature\Payment\WebhookTest covers
 * the business logic once a request is already known-valid, using a
 * fake gateway whose signature check is a no-op; this file is what
 * actually proves the HMAC verification itself works.
 */
class StripeWebhookSignatureTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'whsec_test_secret_for_phase_23';

    protected function setUp(): void
    {
        parent::setUp();
        config(['payment.gateways.stripe.webhook_secret' => self::SECRET]);
    }

    private function postWebhook(string $body, string $header): TestResponse
    {
        return $this->call(
            'POST',
            '/api/v1/webhooks/stripe',
            [],
            [],
            [],
            $this->transformHeadersToServerVars(['Content-Type' => 'application/json', 'Stripe-Signature' => $header]),
            $body,
        );
    }

    public function test_a_correctly_signed_webhook_is_accepted(): void
    {
        $body = json_encode(['id' => 'evt_sig_ok', 'type' => 'customer.updated', 'data' => ['object' => ['id' => 'cus_1']]]);
        $timestamp = time();
        $signature = hash_hmac('sha256', "{$timestamp}.{$body}", self::SECRET);

        $this->postWebhook($body, "t={$timestamp},v1={$signature}")->assertOk();
    }

    public function test_a_webhook_signed_with_the_wrong_secret_is_rejected(): void
    {
        $body = json_encode(['id' => 'evt_sig_bad', 'type' => 'customer.updated', 'data' => ['object' => ['id' => 'cus_1']]]);
        $timestamp = time();
        $signature = hash_hmac('sha256', "{$timestamp}.{$body}", 'whsec_wrong_secret');

        $this->postWebhook($body, "t={$timestamp},v1={$signature}")->assertStatus(400);
    }

    public function test_a_webhook_with_a_stale_timestamp_is_rejected(): void
    {
        $body = json_encode(['id' => 'evt_sig_stale', 'type' => 'customer.updated', 'data' => ['object' => ['id' => 'cus_1']]]);
        $timestamp = time() - 3600; // well outside the default 5-minute tolerance
        $signature = hash_hmac('sha256', "{$timestamp}.{$body}", self::SECRET);

        $this->postWebhook($body, "t={$timestamp},v1={$signature}")->assertStatus(400);
    }

    public function test_a_webhook_with_no_signature_header_is_rejected(): void
    {
        $body = json_encode(['id' => 'evt_sig_missing', 'type' => 'customer.updated', 'data' => ['object' => ['id' => 'cus_1']]]);

        $this->call('POST', '/api/v1/webhooks/stripe', [], [], [], $this->transformHeadersToServerVars(['Content-Type' => 'application/json']), $body)
            ->assertStatus(400);
    }

    public function test_a_webhook_is_rejected_when_no_webhook_secret_is_configured(): void
    {
        config(['payment.gateways.stripe.webhook_secret' => null]);
        $body = json_encode(['id' => 'evt_sig_unconfigured', 'type' => 'customer.updated', 'data' => ['object' => ['id' => 'cus_1']]]);
        $timestamp = time();
        $signature = hash_hmac('sha256', "{$timestamp}.{$body}", 'whsec_whatever');

        $this->postWebhook($body, "t={$timestamp},v1={$signature}")->assertStatus(400);
    }
}
