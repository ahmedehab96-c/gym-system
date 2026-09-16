<?php

namespace App\Http\Controllers\Api\Payment;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\PaymentWebhookEvent;
use App\Services\Payment\PaymentWebhookHandler;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * The public gateway webhook endpoint — outside auth:sanctum entirely
 * (see routes/api.php), gated instead by the 'payment.webhook' signature
 * middleware (VerifyPaymentWebhookSignature). Every event is written to
 * payment_webhook_events keyed on its gateway-issued id BEFORE it's acted
 * on, inside the same transaction as the handling itself — a duplicate
 * delivery (the same event id twice) either finds the row already
 * committed (fast path below) or collides on the unique index and rolls
 * back cleanly (the QueryException catch), so no event is ever acted on
 * twice. If handling genuinely fails, the whole transaction rolls back
 * (nothing recorded) and a 5xx is returned so the gateway retries.
 */
class PaymentWebhookController extends Controller
{
    public function __construct(private readonly PaymentWebhookHandler $handler) {}

    public function stripe(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);
        $eventId = $payload['id'] ?? null;
        $type = $payload['type'] ?? null;
        $data = $payload['data']['object'] ?? [];

        if (! is_string($eventId) || ! is_string($type) || ! is_array($data)) {
            return ApiResponse::error('Malformed webhook payload.', [], 400);
        }

        if (PaymentWebhookEvent::where('provider', 'stripe')->where('event_id', $eventId)->exists()) {
            return ApiResponse::message('Event already processed.');
        }

        try {
            DB::transaction(function () use ($eventId, $type, $data) {
                PaymentWebhookEvent::create([
                    'provider' => 'stripe',
                    'event_id' => $eventId,
                    'type' => $type,
                    'status' => 'Processed',
                    'payload' => $this->safeSummary($data),
                ]);

                $this->handler->handle($type, $data);
            });
        } catch (QueryException $e) {
            // A concurrent delivery of the same event id won the race.
            return ApiResponse::message('Event already processed.');
        } catch (\Throwable $e) {
            Log::error('Payment webhook processing failed.', ['event_id' => $eventId, 'type' => $type, 'error' => $e->getMessage()]);
            throw $e;
        }

        return ApiResponse::message('Webhook processed.');
    }

    /**
     * A small, curated, non-sensitive summary — never the full raw
     * payload (which can carry customer PII beyond what this app needs)
     * and never anything resembling a card number or secret (Stripe
     * itself never includes those in webhook payloads either).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function safeSummary(array $data): array
    {
        return array_filter([
            'id' => $data['id'] ?? null,
            'object' => $data['object'] ?? null,
            'status' => $data['status'] ?? $data['payment_status'] ?? null,
            'amount' => $data['amount_total'] ?? $data['amount_paid'] ?? $data['amount_due'] ?? $data['amount_refunded'] ?? null,
            'currency' => $data['currency'] ?? null,
            'subscription' => is_string($data['subscription'] ?? null) ? $data['subscription'] : null,
        ], fn ($value) => $value !== null);
    }
}
