<?php

namespace App\Http\Middleware;

use App\Http\Responses\ApiResponse;
use App\Services\Payment\Contracts\PaymentGatewayContract;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * The actual security boundary on the public webhook endpoint (Phase 23
 * §4/§9) — this route has no auth:sanctum token to check, so a verified
 * gateway signature is the only thing standing between an attacker and
 * a forged "payment succeeded" event. Delegates the algorithm to the
 * bound PaymentGatewayContract so it stays provider-independent, same
 * as everything else in App\Services\Payment.
 */
class VerifyPaymentWebhookSignature
{
    public function __construct(private readonly PaymentGatewayContract $gateway) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->gateway->verifyWebhookSignature($request->getContent(), $request)) {
            Log::warning('Rejected a payment webhook request: invalid or missing signature.');

            return ApiResponse::error('Invalid webhook signature.', [], 400);
        }

        return $next($request);
    }
}
