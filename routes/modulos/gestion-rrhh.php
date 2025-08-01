<?php

use App\Constants\Permisos;
use App\Http\Controllers\ConductorController;
use App\Http\Controllers\IncapacidadController;
use App\Http\Controllers\ModuloController;
use App\Http\Controllers\SeguimientoIncapacidadController;
use Illuminate\Support\Facades\Route;


//Rutas Modulo Gestion RRHH
Route::prefix("gestionRRHH")->middleware(['auth', 'permisos:'.Permisos::GESTION_RRHH_ACCEDER,'modulo.activo:2'])->group(function(){
    Route::prefix("gestion-empleado")->middleware(['auth', 'permisos:'.Permisos::GESTION_RRHH_GESTION_EMPLEADO_ACCEDER,'submodulo.activo:7'])->group(function(){
        Route::get("/",[ModuloController::class,"getGestionEmpleado"])->name("gestion-incapacidad.index");
        Route::get("/incapacidades",[IncapacidadController::class,"listaIncapacidades"])->name("gestion-empleado.incapacidades");
        Route::get("/incapacidades/cargarDatos",[IncapacidadController::class,"cargarDatos"])->middleware('soloAJAX')->name("gestion-empleado.incapacidades.cargarDatos");
        Route::get("/incapacidades/{id}/datos",[IncapacidadController::class,"editIncapacidad"])->middleware('soloAJAX')->name("gestion-empleado.incapacidades.edit");
        Route::put("/incapacidades/{id}/datos",[IncapacidadController::class,"updateIncapacidad"])->name("gestion-empleado.incapacidades.update");
        Route::get("/incapacidades/{id}/gestion",[IncapacidadController::class,"gestionIncapacidad"])->middleware('soloAJAX')->name("gestion-empleado.incapacidades.gestion");
        Route::put("/incapacidades/{id}/estado",[IncapacidadController::class,"updateEstadoIncapacidad"])->name("gestion-empleado.incapacidades.estado");

        //Esta ruta carga adjuntos para la incapacidad y para el seguimiento
        Route::get("/seguimiento/{id}/adjuntos",[IncapacidadController::class,"incapacidadAdjuntos"])->middleware('soloAJAX')->name("gestion-empleado.seguimiento.adjuntos");
        Route::get("/seguimiento",[SeguimientoIncapacidadController::class,"incapacidadesSeguimiento"])->name("gestion-empleado.seguimiento");
        Route::get("/seguimiento/cargarDatos",[SeguimientoIncapacidadController::class,"cargarDatosSeguimiento"])->middleware('soloAJAX')->name("gestion-empleado.seguimiento.cargarDatos");
        Route::get("/seguimiento/{id}/registro",[SeguimientoIncapacidadController::class,"seguimientoDetalle"])->name("gestion-empleado.seguimiento.detalle");
        Route::get("/seguimiento/{id}/nuevo-seguimiento",[SeguimientoIncapacidadController::class,"nuevoSeguimiento"])->name("gestion-empleado.seguimiento.detalle.crear");
        Route::post("/seguimiento/{id}/nuevo-seguimiento",[SeguimientoIncapacidadController::class,"guardarSeguimiento"])->name("gestion-empleado.seguimiento.detalle.store");

        //Registrar descanso conductores
        Route::get("/formDescansoConductores",[ConductorController::class,"formDescansoConductores"])->name("conductor.descanso");
        Route::post("/descansoConductores",[ConductorController::class,"registrarEvento"])->name("registrar.evento");
    });
});
