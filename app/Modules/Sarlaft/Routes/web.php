<?php

declare(strict_types=1);

use App\Modules\Sarlaft\Http\Controllers\Admin\AlertaController;
use App\Modules\Sarlaft\Http\Controllers\Admin\ListaNegraController;
use App\Modules\Sarlaft\Http\Controllers\Admin\SincronizacionController;
use App\Modules\Sarlaft\Http\Controllers\Admin\SistemaConsumidorController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('sarlaft')->name('sarlaft.')->group(function (): void {
    Route::get('/', [AlertaController::class, 'index'])->name('dashboard');

    Route::get('/alertas', [AlertaController::class, 'index'])->name('alertas.index');
    Route::get('/alertas/{alerta}', [AlertaController::class, 'show'])->name('alertas.show');
    Route::patch('/alertas/{alerta}/atender', [AlertaController::class, 'atender'])->name('alertas.atender');
    Route::get('/alertas/{alerta}/evidencias/{evidencia}', [AlertaController::class, 'descargarEvidencia'])
        ->name('alertas.evidencias.download');

    Route::get('/lista-negra/{lista_negra}/evidencias/{tipo}', [ListaNegraController::class, 'descargarEvidencia'])
        ->whereIn('tipo', ['inclusion', 'retiro'])
        ->name('lista-negra.evidencias.download');

    Route::resource('lista-negra', ListaNegraController::class)->parameters([
        'lista-negra' => 'lista_negra',
    ]);

    Route::get('/sistemas-consumidores', [SistemaConsumidorController::class, 'index'])->name('sistemas-consumidores.index');
    Route::post('/sistemas-consumidores', [SistemaConsumidorController::class, 'store'])->name('sistemas-consumidores.store');
    Route::patch('/sistemas-consumidores/{sistema_consumidor}', [SistemaConsumidorController::class, 'update'])->name('sistemas-consumidores.update');
    Route::delete('/sistemas-consumidores/{sistema_consumidor}', [SistemaConsumidorController::class, 'destroy'])->name('sistemas-consumidores.destroy');

    Route::get('/sincronizacion', [SincronizacionController::class, 'index'])->name('sincronizacion.index');
    Route::post('/sincronizacion/listas', [SincronizacionController::class, 'storeLista'])->name('sincronizacion.listas.store');
    Route::post('/sincronizacion/listas/sincronizar-config', [SincronizacionController::class, 'sincronizarDesdeConfig'])->name('sincronizacion.listas.sincronizar-config');
    Route::post('/sincronizacion/listas/sincronizar-ahora', [SincronizacionController::class, 'sincronizarListasAhora'])
        ->middleware('role:SUPER-ADMIN|ADMIN')
        ->name('sincronizacion.listas.sincronizar-ahora');
    Route::post('/sincronizacion/intentos/sincronizar-ahora', [SincronizacionController::class, 'sincronizarIntentosAhora'])
        ->middleware('role:SUPER-ADMIN|ADMIN')
        ->name('sincronizacion.intentos.sincronizar-ahora');
});
