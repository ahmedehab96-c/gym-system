<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default AI Provider
    |--------------------------------------------------------------------------
    |
    | Which entry under "providers" below App\Providers\AppServiceProvider
    | binds App\Services\AI\Contracts\AIProviderContract to. Business
    | services (GymAssistantService, InsightService, ...) never reference
    | a concrete provider directly, so switching this doesn't touch them.
    */
    'default' => env('AI_PROVIDER', 'openai'),

    'providers' => [
        'openai' => [
            // Any OpenAI-compatible /chat/completions endpoint works here
            // (OpenAI itself, Azure OpenAI, or a self-hosted gateway) —
            // only the base URL and model need to change per environment.
            'api_key' => env('OPENAI_API_KEY'),
            'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
            'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
            'timeout' => (int) env('AI_TIMEOUT_SECONDS', 20),
        ],
    ],

    'max_tokens' => (int) env('AI_MAX_TOKENS', 500),

    'temperature' => (float) env('AI_TEMPERATURE', 0.3),

];
