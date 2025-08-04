<?php

use App\Constants\Permisos;
use App\Http\Controllers\PagosYConveniosController;
use Illuminate\Support\Facades\Route;

//Rutas modulo pagos y recaudos
Route::prefix("pagos-recaudos")->middleware(['auth', 'permisos:'.Permisos::PAGOS_Y_RECAUDOS_ACCEDER,'modulo.activo:16'])->group(function(){

    Route::prefix("pago-convenio")->middleware(['permisos:'.Permisos::PAGOS_Y_RECAUDOS_PAGOS_CONVENIOS_ACCEDER,'submodulo.activo:26','caja.activa'])->group(function(){

        Route::get("/",[PagosYConveniosController::class,"index"])->name("pagosConvenios.index");
        Route::post("/consultar",[PagosYConveniosController::class,"consultar"])->name("pagosConvenios.consultar");
        Route::get("/validar-pago",[PagosYConveniosController::class,"validarModal"])->name("pagosConvenios.validarModal");
    });
});
