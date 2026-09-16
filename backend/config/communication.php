<?php

return [

    /*
    |--------------------------------------------------------------------------
    | WhatsApp / Push provider selection
    |--------------------------------------------------------------------------
    |
    | Same provider-swap pattern as config/ai.php and config/payment.php —
    | App\Providers\AppServiceProvider is the only place that picks a
    | concrete implementation of the Contracts below.
    */
    'whatsapp_provider' => env('WHATSAPP_PROVIDER', 'whatsapp_cloud_api'),

    'whatsapp' => [
        'whatsapp_cloud_api' => [
            // Meta's official WhatsApp Business Cloud API — never a
            // third-party/unofficial gateway (see Phase 24 §4).
            'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
            'access_token' => env('WHATSAPP_ACCESS_TOKEN'),
            'api_version' => env('WHATSAPP_API_VERSION', 'v20.0'),
            'base_url' => env('WHATSAPP_BASE_URL', 'https://graph.facebook.com'),
            'timeout' => (int) env('WHATSAPP_TIMEOUT_SECONDS', 15),
        ],
    ],

    'push_provider' => env('PUSH_PROVIDER', 'fcm'),

    'push' => [
        'fcm' => [
            // Firebase Cloud Messaging's HTTP v1 API needs a short-lived
            // OAuth2 bearer token (from a service account) rather than a
            // static server key — this app treats FCM_ACCESS_TOKEN as an
            // already-minted bearer token supplied by the deployer/ops
            // process; refreshing it is intentionally out of scope here
            // (see Phase 24 report — no mobile client exists to test
            // against yet, this only prepares the server-side contract).
            'project_id' => env('FCM_PROJECT_ID'),
            'access_token' => env('FCM_ACCESS_TOKEN'),
            'base_url' => env('FCM_BASE_URL', 'https://fcm.googleapis.com/v1'),
            'timeout' => (int) env('PUSH_TIMEOUT_SECONDS', 15),
        ],
    ],

];
