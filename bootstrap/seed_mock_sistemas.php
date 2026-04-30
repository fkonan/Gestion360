<?php

use App\Modules\Sarlaft\Models\SistemaConsumidor;
use Illuminate\Support\Str;

$base = 'http://autogestion2.test/api/mock/sistema-externo';

$sistemas = [
    ['codigo' => 'logtrans', 'nombre' => 'Logtrans'],
    ['codigo' => 'odin',     'nombre' => 'Odin'],
    ['codigo' => '360',      'nombre' => '360'],
    ['codigo' => 'fics',     'nombre' => 'FICS'],
];

foreach ($sistemas as $s) {
    $creado = SistemaConsumidor::firstOrCreate(
        ['codigo' => $s['codigo']],
        [
            'nombre'                 => $s['nombre'],
            'api_token'              => Str::random(64),
            'estado'                 => 'activo',
            'limite_requests_minuto' => 100,
            'pull_endpoint'          => $base . '?sistema=' . $s['codigo'],
            'pull_token'             => null,
        ]
    );
    echo ($creado->wasRecentlyCreated ? 'CREADO' : 'YA EXISTE') . ': ' . $s['codigo'] . PHP_EOL;
}

echo PHP_EOL . 'Listo. Sistemas con Pull configurado:' . PHP_EOL;
SistemaConsumidor::whereNotNull('pull_endpoint')->get(['codigo', 'nombre', 'pull_endpoint'])->each(function ($s) {
    echo "  [{$s->codigo}] {$s->nombre} → {$s->pull_endpoint}" . PHP_EOL;
});
