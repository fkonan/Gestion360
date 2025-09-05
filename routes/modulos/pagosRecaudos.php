<?php

use App\Constants\Permisos;
use App\Http\Controllers\PagosConveniosCajasanController;
use Illuminate\Support\Facades\Route;

//Rutas modulo pagos y recaudos
Route::prefix("pagos-recaudos")->middleware(['auth', 'permisos:'.Permisos::PAGOS_Y_RECAUDOS_ACCEDER,'modulo.activo:16'])->group(function(){

    Route::prefix("pago-convenio")->middleware(['permisos:'.Permisos::PAGOS_Y_RECAUDOS_PAGOS_CONVENIOS_ACCEDER,'submodulo.activo:26'])->group(function(){

        Route::get("/",[PagosConveniosCajasanController::class,"index"])->middleware(['caja.activa'])->name("pagosConvenios.index");
        Route::post("/consultar",[PagosConveniosCajasanController::class,"consultar"])->name("pagosConvenios.consultar");
        Route::get("/validar-pago/{uuid}",[PagosConveniosCajasanController::class,"validarInformacion"])->name("pagosConvenios.validarInformacion");
        Route::post("/pagar",[PagosConveniosCajasanController::class,"pagar"])->name("pagosConvenios.pagar");
        Route::get("/generar-recibo/{IdDetallePago}",[PagosConveniosCajasanController::class,"generarRecibo"])->name("pagosConvenios.recibo");
    });
});
