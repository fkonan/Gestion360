<?php

declare(strict_types=1);

use App\Modules\Sarlaft\Http\Controllers\Api\ConsultaController;
use App\Modules\Sarlaft\Http\Controllers\Api\IntentoOperacionController;
use App\Modules\Sarlaft\Http\Controllers\Api\ListaRegistroController;
use App\Modules\Sarlaft\Http\Controllers\Api\MockSistemaExternoController;
use App\Modules\Sarlaft\Http\Middleware\AutenticarSistemaConsumidor;
use App\Modules\Sarlaft\Http\Middleware\RateLimitSistema;
use Illuminate\Support\Facades\Route;

/*
 * Mock que simula los endpoints externos que los sistemas consumidores
 * expondrán para el modo Pull. Cuando tengamos las URLs reales, se
 * actualiza pull_endpoint en sarlaft_sistemas_consumidores y listo.
 *
 * Restricciones:
 *  - Solo se registra en entornos NO productivos (local, staging, testing).
 *  - Requiere Bearer token de un sistema consumidor activo.
 *  - Sujeto al rate limit por sistema.
 *
 * URL:  GET /api/mock/sistema-externo?sistema=logtrans&fecha_desde=2026-04-29
 */
if (! app()->isProduction()) {
    Route::prefix('mock')
        ->middleware([AutenticarSistemaConsumidor::class, RateLimitSistema::class])
        ->group(function (): void {
            Route::get('/sistema-externo', [MockSistemaExternoController::class, 'index'])
                ->name('sarlaft.mock.sistema-externo');
        });
}

Route::prefix('v1')
    ->middleware([AutenticarSistemaConsumidor::class, RateLimitSistema::class])
    ->group(function (): void {
        Route::post('/consulta', [ConsultaController::class, 'store']);
        Route::post('/consulta/lote', [ConsultaController::class, 'storeLote']);
        Route::get('/consulta/{consulta}', [ConsultaController::class, 'show']);

        Route::get('/listas/registros', [ListaRegistroController::class, 'index']);

        Route::post('/intentos-operacion', [IntentoOperacionController::class, 'store']);
    });
