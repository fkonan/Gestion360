<?php

declare(strict_types=1);

use App\Modules\Sarlaft\Http\Controllers\Api\ConsultaController;
use App\Modules\Sarlaft\Http\Middleware\AutenticarSistemaConsumidor;
use App\Modules\Sarlaft\Http\Middleware\RateLimitSistema;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->middleware([AutenticarSistemaConsumidor::class, RateLimitSistema::class])
    ->group(function (): void {
        Route::post('/consulta', [ConsultaController::class, 'store']);
        Route::post('/consulta/lote', [ConsultaController::class, 'storeLote']);
        Route::get('/consulta/{consulta}', [ConsultaController::class, 'show']);
    });
