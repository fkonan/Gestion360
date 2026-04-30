<?php

use App\Modules\Sarlaft\Models\SistemaConsumidor;

$base = 'http://127.0.0.1:8100/api/mock/sistema-externo';

foreach (['logtrans', 'odin', '360', 'fics'] as $codigo) {
    $updated = SistemaConsumidor::where('codigo', $codigo)
        ->update(['pull_endpoint' => $base . '?sistema=' . $codigo]);
    echo ($updated ? 'OK' : 'NO ENCONTRADO') . ': ' . $codigo . PHP_EOL;
}
