<?php

declare(strict_types=1);

use App\Modules\Sarlaft\Http\Controllers\Api\AuthController;
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

Route::prefix('v1')->group(function (): void {
    // Emision de token JWT (client_credentials). Valida client_id/secret contra
    // la tabla de sistemas consumidores; sin auth previa. Rate limit por client_id
    // (anti fuerza bruta) ademas de por IP.
    Route::post('/auth/token', [AuthController::class, 'token'])
        ->middleware('throttle:sarlaft-token')
        ->name('sarlaft.api.auth.token');

    // Consulta puntual de coincidencia en listas: JWT + scope. El throttle va
    // DESPUES del jwt.api para poder limitar por sistema (claim del token).
    Route::post('/listas/consultar', [ListaRegistroController::class, 'consultar'])
        ->middleware(['jwt.api:sarlaft.listas.consultar', 'throttle:sarlaft-consulta'])
        ->name('sarlaft.api.listas.consultar');

    // Endpoints existentes de sistemas Bearer (Logtrans/Odin): sin cambios.
    Route::middleware([AutenticarSistemaConsumidor::class, RateLimitSistema::class])
        ->group(function (): void {
            Route::get('/listas/registros', [ListaRegistroController::class, 'index']);
            Route::post('/intentos-operacion', [IntentoOperacionController::class, 'store']);
        });
});
