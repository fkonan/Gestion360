<?php

return [
    'url' => env('CAMERA_SERVICE_URL'),
    'key' => env('CAMERA_SERVICE_KEY'),
    'allowed_networks' => array_values(array_filter(array_map(
        static fn ($value) => trim((string) $value),
        explode(',', (string) env('CAMERA_ALLOWED_CIDRS', '172.16.0.0/12,127.0.0.1/32,::1/128'))
    ))),
];
