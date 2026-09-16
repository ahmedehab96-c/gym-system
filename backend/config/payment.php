<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Payment Gateway
    |--------------------------------------------------------------------------
    |
    | Which entry under "gateways" below App\Providers\AppServiceProvider
    | binds App\Services\Payment\Contracts\PaymentGatewayContract to.
    | Business services (CheckoutService, RefundService, ...) never
    | reference a concrete gateway directly, so switching this doesn't
    | touch them — mirrors config/ai.php's provider-swap pattern.
    */
    'default' => env('PAYMENT_GATEWAY', 'stripe'),

    'gateways' => [
        'stripe' => [
            // Server-side only — never sent to the frontend. The
            // publishable key is safe for the frontend, but nothing in
            // this app needs it yet since checkout is fully provider-
            // hosted (Stripe Checkout), not client-side Elements/tokenization.
            'secret_key' => env('STRIPE_SECRET_KEY'),
            'publishable_key' => env('STRIPE_PUBLISHABLE_KEY'),
            'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
            'base_url' => env('STRIPE_BASE_URL', 'https://api.stripe.com/v1'),
            'timeout' => (int) env('PAYMENT_TIMEOUT_SECONDS', 20),
        ],
    ],

    'currency' => env('PAYMENT_CURRENCY', 'usd'),

    // {CHECKOUT_SESSION_ID} is replaced by Stripe itself on redirect — the
    // literal placeholder must reach Stripe unencoded, see CheckoutService.
    'checkout_success_url' => env(
        'PAYMENT_CHECKOUT_SUCCESS_URL',
        rtrim(env('FRONTEND_URL', 'http://localhost:5173'), '/').'/admin/billing?checkout=success&session_id={CHECKOUT_SESSION_ID}',
    ),

    'checkout_cancel_url' => env(
        'PAYMENT_CHECKOUT_CANCEL_URL',
        rtrim(env('FRONTEND_URL', 'http://localhost:5173'), '/').'/admin/billing?checkout=cancelled',
    ),

    // Replay-attack tolerance for webhook signature timestamps, in seconds.
    'webhook_tolerance_seconds' => (int) env('PAYMENT_WEBHOOK_TOLERANCE_SECONDS', 300),

];
