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
    'token_timeout_seconds' => max(1, (int) env('API_ASOPAGOS_TOKEN_TIMEOUT_SECONDS', 30)),
    'transaction_timeout_seconds' => max(1, (int) env('API_ASOPAGOS_TRANSACTION_TIMEOUT_SECONDS', 30)),
    'token_retry_attempts' => max(1, (int) env('API_ASOPAGOS_TOKEN_RETRY_ATTEMPTS', 1)),
    'token_retry_sleep_ms' => max(0, (int) env('API_ASOPAGOS_TOKEN_RETRY_SLEEP_MS', 200)),

    'test_mode' => env('API_ASOPAGOS_TEST_MODE', false),
    'provider_mode' => env('API_ASOPAGOS_PROVIDER_MODE', env('API_ASOPAGOS_TEST_MODE', false) ? 'mock' : 'real'),
    'persistence_mode' => env('API_ASOPAGOS_PERSISTENCE_MODE', 'real'),

    'mock' => [
        'consulta_saldo_scenario' => env('API_ASOPAGOS_MOCK_CONSULTA_SALDO_SCENARIO', 'success_with_balance'),
        'pago_scenario' => env('API_ASOPAGOS_MOCK_PAGO_SCENARIO', 'success'),
        'saldo' => env('API_ASOPAGOS_MOCK_SALDO', 58000),
    ],
];
