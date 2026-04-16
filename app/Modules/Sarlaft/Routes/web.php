<?php

declare(strict_types=1);

use App\Modules\Sarlaft\Http\Controllers\Admin\AlertaController;
use App\Modules\Sarlaft\Http\Controllers\Admin\BloqueoController;
use App\Modules\Sarlaft\Http\Controllers\Admin\DashboardController;
use App\Modules\Sarlaft\Http\Controllers\Admin\ListaNegraController;
use App\Modules\Sarlaft\Http\Controllers\Admin\PoliticaController;
use App\Modules\Sarlaft\Http\Controllers\Admin\ReporteOperacionesController;
use App\Modules\Sarlaft\Http\Controllers\Admin\SimulacionController;
use App\Modules\Sarlaft\Http\Controllers\Admin\SincronizacionController;
use App\Modules\Sarlaft\Http\Controllers\Admin\SistemaConsumidorController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('sarlaft')->name('sarlaft.')->group(function (): void {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/politicas', [PoliticaController::class, 'edit'])->name('politicas.edit');
    Route::put('/politicas', [PoliticaController::class, 'update'])->name('politicas.update');

    Route::get('/alertas', [AlertaController::class, 'index'])->name('alertas.index');
    Route::get('/alertas/{alerta}', [AlertaController::class, 'show'])->name('alertas.show');
    Route::patch('/alertas/{alerta}/atender', [AlertaController::class, 'atender'])->name('alertas.atender');
    Route::get('/alertas/{alerta}/evidencias/{evidencia}', [AlertaController::class, 'descargarEvidencia'])
        ->name('alertas.evidencias.download');

    Route::get('/bloqueos', [BloqueoController::class, 'index'])->name('bloqueos.index');
    Route::get('/bloqueos/crear', [BloqueoController::class, 'create'])->name('bloqueos.create');
    Route::post('/bloqueos', [BloqueoController::class, 'store'])->name('bloqueos.store');
    Route::get('/bloqueos/{bloqueo}', [BloqueoController::class, 'show'])->name('bloqueos.show');
    Route::patch('/bloqueos/{bloqueo}', [BloqueoController::class, 'update'])->name('bloqueos.update');

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
    Route::post('/sincronizacion/sincronizar-ahora', [SincronizacionController::class, 'sincronizarAhora'])->name('sincronizacion.sincronizar-ahora');

    Route::get('/simulaciones', [SimulacionController::class, 'index'])->name('simulaciones.index');
    Route::post('/simulaciones/pasajes', [SimulacionController::class, 'storePasaje'])->name('simulaciones.pasajes.store');
    Route::post('/simulaciones/remesas', [SimulacionController::class, 'storeRemesa'])->name('simulaciones.remesas.store');

    Route::get('/reportes/operaciones', [ReporteOperacionesController::class, 'index'])->name('reportes.operaciones.index');
});
