<?php

return [
    'token_url' => env('API_ASOPAGOS_TOKEN_URL'),
    'base_url' => env('API_ASOPAGOS_BASE_URL'),

    'credentials' => [
        'username' => env('API_ASOPAGOS_USERNAME'),
        'password' => env('API_ASOPAGOS_PASSWORD'),

        'auth_username' => env('API_ASOPAGOS_AUTH_USERNAME'),
        'auth_password' => env('API_ASOPAGOS_AUTH_PASSWORD'),

        'client_id' => env('API_ASOPAGOS_CLIENT_ID'),
        'client_secret' => env('API_ASOPAGOS_CLIENT_SECRET'),
        'scope' => env('API_ASOPAGOS_SCOPE'),
    ],

    'partner_id' => env('API_ASOPAGOS_PARTNER_ID'),
    'origin_id' => env('API_ASOPAGOS_ORIGIN_ID'),
    'client_id_value' => env('API_ASOPAGOS_CLIENT_ID_VALUE'),

    'token_cache_key' => 'api_asopagos_token2',
    'token_cache_minutes' => 30,

    'test_mode' => env('API_ASOPAGOS_TEST_MODE', false),
];
