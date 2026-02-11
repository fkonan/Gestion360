<?php

use App\Constants\Permisos;
use App\Modules\PagosRecaudos\Http\Controllers\CajasanController;
use Illuminate\Support\Facades\Route;

// Rutas modulo pagos y recaudos
Route::prefix('pagos-recaudos')->middleware(['auth', 'permisos:'.Permisos::PAGOS_Y_RECAUDOS_ACCEDER, 'modulo.activo:16'])->group(function () {

    Route::prefix('pago-convenio')->middleware(['permisos:'.Permisos::PAGOS_Y_RECAUDOS_PAGOS_CONVENIOS_ACCEDER, 'submodulo.activo:26'])->group(function () {

        Route::get('/', [CajasanController::class, 'index'])->middleware(['caja.activa'])->name('pagosConvenios.index');
        Route::post('/consultar', [CajasanController::class, 'consultar'])->name('pagosConvenios.consultar');
        Route::get('/validar-pago/{uuid}', [CajasanController::class, 'validarInformacion'])->name('pagosConvenios.validarInformacion');
        Route::post('/pagar', [CajasanController::class, 'pagar'])->name('pagosConvenios.pagar');
        Route::get('/generar-recibo/{IdDetallePago}', [CajasanController::class, 'generarRecibo'])->name('pagosConvenios.recibo');
    });
});
