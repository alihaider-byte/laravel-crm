<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Service Account Configuration
    |--------------------------------------------------------------------------
    |
    | This service account is used for auto-authentication when external
    | systems (like LIBBi) call the API without providing a token.
    |
    */
    'service_account' => [
        'email' => env('CRM_SERVICE_EMAIL', 'admin@example.com'),
        'password' => env('CRM_SERVICE_PASSWORD', 'admin123'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Token Cache Settings
    |--------------------------------------------------------------------------
    |
    | Configure how the service account token is cached.
    |
    */
    'token_cache' => [
        'key' => 'crm_service_account_token',
        'ttl' => 60 * 24 * 7, // 7 days in minutes
    ],
];
