<?php

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

$url = 'http://127.0.0.1:8100/api/mock/sistema-externo?sistema=logtrans';

// Mismo código que IntentoOperacionService::ejecutarPull
$response = Http::withToken('')
    ->timeout(30)
    ->get($url, [
        'fecha_desde' => now()->subDay()->toIso8601String(),
        'fecha_hasta'  => now()->toIso8601String(),
    ]);

echo 'Status: ' . $response->status() . PHP_EOL;
echo 'Successful: ' . ($response->successful() ? 'SI' : 'NO') . PHP_EOL;
echo 'Body (primeros 500 chars): ' . PHP_EOL;
echo substr($response->body(), 0, 500) . PHP_EOL;
echo PHP_EOL;
$data = $response->json('data');
echo 'json(data) count: ' . (is_array($data) ? count($data) : 'NULL') . PHP_EOL;
