<?php

return [
    'base_url' => env('BRONTOBYTE_API_BASE_URL', env('BRONTOBYTE_BASE_URL')),
    'username' => env('BRONTOBYTE_API_USERNAME', env('BRONTOBYTE_USERNAME')),
    'password' => env('BRONTOBYTE_API_PASSWORD', env('BRONTOBYTE_PASSWORD')),

    'connect_timeout' => (float) env('BRONTOBYTE_API_CONNECT_TIMEOUT', env('BRONTOBYTE_CONNECT_TIMEOUT', 5)),
    'timeout' => (float) env('BRONTOBYTE_API_TIMEOUT', env('BRONTOBYTE_TIMEOUT', 20)),

    'token_cache_key' => env('BRONTOBYTE_API_TOKEN_CACHE_KEY', env('BRONTOBYTE_TOKEN_CACHE_KEY', 'api_brontobyte_token')),
    'token_cache_minutes' => (int) env('BRONTOBYTE_API_TOKEN_CACHE_MINUTES', env('BRONTOBYTE_TOKEN_CACHE_MINUTES', 55)),

    'log_days' => (int) env('BRONTOBYTE_API_LOG_DAYS', env('BRONTOBYTE_LOG_DAYS', 7)),
];
