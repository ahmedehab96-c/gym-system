<?php

namespace App\Services\Payment\Providers;

use App\Services\Payment\Contracts\PaymentGatewayContract;
use App\Services\Payment\DTO\CheckoutSession;
use App\Services\Payment\DTO\CheckoutSessionStatus;
use App\Services\Payment\DTO\GatewaySubscription;
use App\Services\Payment\DTO\RefundResult;
use App\Services\Payment\Exceptions\PaymentGatewayException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

/**
 * Talks to Stripe's REST API directly via Laravel's HTTP client — no
 * stripe/stripe-php SDK dependency, mirroring how App\Services\AI's
 * OpenAIProvider calls a JSON API with plain Http:: calls instead of a
 * vendor SDK. Stripe's API accepts application/x-www-form-urlencoded
 * bodies with PHP's native bracket notation for nested params, which is
 * exactly what Http::asForm()->post() already produces for a nested
 * array — no extra encoding step needed.
 */
class StripeProvider implements PaymentGatewayContract
{
    /**
     * @param  array{secret_key: ?string, publishable_key: ?string, webhook_secret: ?string, base_url: string, timeout: int}  $config
     */
    public function __construct(private readonly array $config) {}

    public function createCheckoutSession(array $params): CheckoutSession
    {
        $payload = [
            'mode' => 'subscription',
            'success_url' => $params['success_url'],
            'cancel_url' => $params['cancel_url'],
            'client_reference_id' => $params['client_reference_id'],
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => $params['currency'],
                    'unit_amount' => $params['unit_amount'],
                    'product_data' => ['name' => $params['product_name']],
                    'recurring' => ['interval' => $params['interval']],
                ],
            ]],
            'metadata' => $params['metadata'],
            'subscription_data' => ['metadata' => $params['metadata']],
        ];

        if (! empty($params['customer_email'])) {
            $payload['customer_email'] = $params['customer_email'];
        }

        $response = $this->post('/checkout/sessions', $payload);

        return new CheckoutSession(id: $response['id'], url: $response['url']);
    }

    public function retrieveCheckoutSession(string $sessionId): CheckoutSessionStatus
    {
        $data = $this->get("/checkout/sessions/{$sessionId}", ['expand' => ['subscription']]);

        $subscription = $data['subscription'] ?? null;
        $periodEnd = is_array($subscription) && isset($subscription['current_period_end'])
            ? Carbon::createFromTimestampUTC($subscription['current_period_end'])
            : null;

        return new CheckoutSessionStatus(
            id: $data['id'],
            status: $data['status'] ?? 'open',
            paymentStatus: $data['payment_status'] ?? 'unpaid',
            subscriptionId: is_array($subscription) ? $subscription['id'] : $subscription,
            customerId: is_string($data['customer'] ?? null) ? $data['customer'] : null,
            paymentIntentId: is_string($data['payment_intent'] ?? null) ? $data['payment_intent'] : null,
            currentPeriodEnd: $periodEnd,
        );
    }

    public function retrieveSubscription(string $subscriptionId): GatewaySubscription
    {
        $data = $this->get("/subscriptions/{$subscriptionId}");

        return new GatewaySubscription(
            id: $data['id'],
            status: $data['status'],
            currentPeriodEnd: Carbon::createFromTimestampUTC($data['current_period_end']),
        );
    }

    public function refund(string $paymentIntentId, ?int $amountMinorUnits = null): RefundResult
    {
        $payload = ['payment_intent' => $paymentIntentId];

        if ($amountMinorUnits !== null) {
            $payload['amount'] = $amountMinorUnits;
        }

        $data = $this->post('/refunds', $payload);

        return new RefundResult(
            reference: $data['id'],
            status: $data['status'],
            amountMinorUnits: $data['amount'],
            currency: $data['currency'],
        );
    }

    public function verifyWebhookSignature(string $payload, Request $request): bool
    {
        $secret = $this->config['webhook_secret'] ?? null;
        $header = $request->header('Stripe-Signature');

        if (! $secret || ! $header) {
            return false;
        }

        $parts = [];
        foreach (explode(',', $header) as $pair) {
            [$key, $value] = array_pad(explode('=', $pair, 2), 2, null);
            if ($key !== null && $value !== null) {
                $parts[$key][] = $value;
            }
        }

        $timestamp = $parts['t'][0] ?? null;
        $signatures = $parts['v1'] ?? [];

        if (! $timestamp || ! ctype_digit($timestamp) || empty($signatures)) {
            return false;
        }

        $tolerance = config('payment.webhook_tolerance_seconds', 300);
        if (abs(time() - (int) $timestamp) > $tolerance) {
            return false;
        }

        $expected = hash_hmac('sha256', "{$timestamp}.{$payload}", $secret);

        foreach ($signatures as $signature) {
            if (hash_equals($expected, $signature)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, mixed>
     */
    private function get(string $path, array $query = []): array
    {
        return $this->send(fn () => $this->client()->get($path, $query));
    }

    /**
     * @return array<string, mixed>
     */
    private function post(string $path, array $payload): array
    {
        return $this->send(fn () => $this->client()->asForm()->post($path, $payload));
    }

    private function client(): PendingRequest
    {
        $secretKey = $this->config['secret_key'] ?? null;

        if (! $secretKey) {
            throw new PaymentGatewayException('The payment gateway is not configured. Set STRIPE_SECRET_KEY in the environment.');
        }

        return Http::withToken($secretKey)
            ->timeout($this->config['timeout'] ?? 20)
            ->baseUrl(rtrim($this->config['base_url'] ?? 'https://api.stripe.com/v1', '/'));
    }

    /**
     * @return array<string, mixed>
     */
    private function send(\Closure $call): array
    {
        try {
            $response = $call();
        } catch (ConnectionException $e) {
            throw new PaymentGatewayException('Could not reach the payment gateway.', previous: $e);
        }

        if ($response->failed()) {
            $message = $response->json('error.message') ?? "The payment gateway returned an error (HTTP {$response->status()}).";
            throw new PaymentGatewayException($message);
        }

        return $response->json();
    }
}
