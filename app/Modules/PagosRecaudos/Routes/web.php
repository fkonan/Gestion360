<?php

use App\Constants\Permisos;
use App\Modules\PagosRecaudos\Http\Controllers\CajasanController;
use App\Modules\PagosRecaudos\Http\Controllers\CajasanRegularizacionController;
use Illuminate\Support\Facades\Route;

// Rutas modulo pagos y recaudos
Route::prefix('pagos-recaudos')->middleware(['auth', 'permisos:'.Permisos::PAGOS_Y_RECAUDOS_ACCEDER, 'modulo.activo:16'])->group(function () {

    Route::prefix('pago-convenio')->middleware(['permisos:'.Permisos::PAGOS_Y_RECAUDOS_PAGOS_CONVENIOS_ACCEDER, 'submodulo.activo:26'])->group(function () {

        Route::get('/regularizaciones', [CajasanRegularizacionController::class, 'index'])->name('pagosConvenios.regularizaciones.index');
        Route::get('/regularizaciones/{detalleId}', [CajasanRegularizacionController::class, 'show'])->name('pagosConvenios.regularizaciones.show');
        Route::post('/regularizaciones/{detalleId}/previsualizar', [CajasanRegularizacionController::class, 'preview'])->name('pagosConvenios.regularizaciones.preview');
        Route::post('/regularizaciones/{detalleId}/regularizar', [CajasanRegularizacionController::class, 'regularizar'])->name('pagosConvenios.regularizaciones.regularizar');

        Route::middleware('caja.activa')->group(function () {

            Route::get('/', [CajasanController::class, 'index'])->name('pagosConvenios.index');
            Route::get('/pagos-hoy', [CajasanController::class, 'historialHoy'])->name('pagosConvenios.historialHoy');
            Route::get('/reversos', [CajasanController::class, 'historialReversos'])->name('pagosConvenios.historialReversos');
            Route::get('/reversos/exportar', [CajasanController::class, 'exportarHistorialReversos'])->name('pagosConvenios.historialReversosExportar');
            Route::post('/consultar', [CajasanController::class, 'consultar'])->name('pagosConvenios.consultar');
            Route::get('/validar-pago/{uuid}', [CajasanController::class, 'validarInformacion'])->name('pagosConvenios.validarInformacion');
            Route::post('/pagar', [CajasanController::class, 'pagar'])->name('pagosConvenios.pagar');
            Route::get('/generar-recibo/{IdDetallePago}', [CajasanController::class, 'generarRecibo'])->name('pagosConvenios.recibo');
        });
    });
});
