<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | The admin panel and marketing site authenticate with a Sanctum bearer
    | token (see Authorization header handling in services/apiClient.ts), not
    | cookies, so `supports_credentials` stays false. Even so, the API is
    | restricted to known frontend origins rather than the framework default
    | of `*`, so a browser page on another origin can't read API responses
    | using a token it obtained some other way.
    |
    */

    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_filter(explode(',', env('FRONTEND_URL', 'http://localhost:5173'))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
