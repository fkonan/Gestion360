<?php

use App\Constants\Permisos;
use App\Http\Controllers\PersonaController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PermisosController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\ReportesController;
use App\Http\Controllers\TiquetesImpresosController;
use App\Http\Controllers\ConductorController;
use App\Http\Controllers\GestionPasajesController;

use Illuminate\Support\Facades\Route;

//Ruta Modulo administración
Route::prefix("administracion")->middleware(['auth', 'permisos:'.Permisos::ADMINISTRACION_ACCEDER,'modulo.activo:12'])->group(function(){
    Route::prefix("personas")->middleware(['permisos:administracion.personas.acceder','submodulo.activo:21'])->group(function(){
        Route::get("/",[PersonaController::class,"index"])->name("personas.index");
        Route::get("/crear",[PersonaController::class,"create"])->name("personas.create");
        Route::post("/",[PersonaController::class,"store"])->name("personas.store");
        Route::get("/{id}/edit",[PersonaController::class,"edit"])->name("personas.edit");
        Route::put("/{id}",[PersonaController::class,"update"])->name("personas.update");
        Route::get("cargarDatos",[PersonaController::class,"cargarDatos"])->middleware('soloAJAX')->name("personas.cargarDatos");
        Route::post("/{id}/cambiar-estado", [PersonaController::class, "cambiarEstado"])->middleware('soloAJAX')->name("personas.cambiarEstado");
    });
    Route::prefix("usuarios")->middleware(['permisos:'.Permisos::ADMINISTRACION_USUARIOS_ACCEDER,'submodulo.activo:20'])->group(function(){
        Route::get("/",[UserController::class,"index"])->name("usuarios.index");
        Route::get("/create",[UserController::class,"create"])->middleware('soloAJAX')->name("usuarios.create");
        Route::post("/",[UserController::class,"store"])->name("usuarios.store");
        Route::get("/{id}/edit",[UserController::class,"edit"])->middleware('soloAJAX')->name("usuarios.edit");
        Route::put("/{id}",[UserController::class,"update"])->name("usuarios.update");
        Route::get("cargarDatos",[UserController::class,"cargarDatos"])->middleware('soloAJAX')->name("usuarios.cargarDatos");
        Route::get("/{id}/roles",[RolController::class,"editRolUsuario"])->middleware('soloAJAX')->name("roles.edit");
        Route::put("/{id}/roles",[RolController::class,"updateRolUsuario"])->name("roles.update");
        Route::get("/{id}/permisos",[PermisosController::class,"edit"])->middleware('soloAJAX')->name("permisos.edit");
        Route::put("/{id}/permisos",[PermisosController::class,"update"])->name("permisos.update");
        Route::post("/{id}/cambiar-estado", [UserController::class, "cambiarEstado"])->middleware('soloAJAX')->name("usuarios.cambiarEstado");
    });

    Route::prefix("reportes")->middleware(['permisos:'.Permisos::ADMINISTRACION_REPORTES_ACCEDER,'submodulo.activo:22'])->group(function(){
        Route::get("/",[ReportesController::class,"getReportes"])->name("reportes.index");
        Route::post("/{id}/data", [ReportesController::class, 'obtenerReporte'])->name('reportes.get');
        Route::get("/{id}/formulario", [ReportesController::class, 'mostrarFormulario'])->name('reportes.formulario');

        //Reportes Conductores
        Route::prefix("conductores")->middleware(['permisos:'.Permisos::ADMINISTRACION_REPORTES_CONDUCTORES])->group(function(){
            Route::get("/",[ReportesController::class,"reportesConductores"])->name("reportes.conductores");

            //Actualizacion estado conductor FICS
            Route::get("/actualizarEstadoModal",[ConductorController::class,"formActualizarEstado"])->middleware('soloAJAX')->name("conductor.estado");
            Route::put("/actualizarEstado",[ConductorController::class,"actualizarEstadoConductor"])->name("conductor.actualizarEstado");

            //Firma politica equipaje
            Route::get("/reporteEquipajeModal",[ConductorController::class,"reporteFirmaEquipaje"])->name("conductor.firmaEquipaje");
            Route::post("/reporteEquipajeModal",[ConductorController::class,"filtrarFirmaEquipaje"])->name("filtrar.firmaEquipaje");
            Route::get("/reporteEquipajeModal/cargarData",[ConductorController::class,"cargarDataFirmaEquipaje"])->middleware('soloAJAX')->name("firmaEquipaje.cargarData");
            Route::get("/reporteEquipajeModal/listaFirmasEquipaje",[ConductorController::class,"listaFirmasEquipaje"])->name("lista.firmaEquipaje");

            //Ingreso y salidas conductores
            Route::get("/formIngSalConductores",[ConductorController::class,"formIngSalConductores"])->name("conductor.ingresoSalidas");
            Route::post("/formIngSalConductores/filtrar",[ConductorController::class,"reporteIngSalConductores"])->name("reporte.ingresoSalidas");
            Route::get("/formIngSalConductores/listaDatos",[ConductorController::class,"listaIngSalConductores"])->name("lista.ingresoSalidas");
            Route::get("/formIngSalConductores/cargarData",[ConductorController::class,"cargarDataIngSalConductores"])->name("ingresoSalida.cargarData");
        });

        //Reportes Pasajes
        Route::prefix("pasajes")->middleware(['permisos:'.Permisos::ADMINISTRACION_REPORTES_PASAJES])->group(function(){
            
            Route::get("/",[ReportesController::class,"reportesPasajes"])->name("reportes.pasajes");

            //Impresion tiquetes
            Route::get("/impresionTiquetes",[TiquetesImpresosController::class,"fechasReporte"])->middleware('soloAJAX')->name("reportes.tiquetes");
            Route::post("/impresionTiquetes/filtrar",[TiquetesImpresosController::class,"filtrarTiquetes"])->name("reportes.filtrarTiquetes");
            Route::get("/impresionTiquetes/listaTiquetes",[TiquetesImpresosController::class,"listaTiquetes"])->name("reportes.listaTiquetes");
            Route::get("/impresionTiquetes/cargarData",[TiquetesImpresosController::class,"cargarDataTiquetes"])->middleware('soloAJAX')->name("reportes.cargarData");

            //Esquema tarifario pasajes
            Route::get("/esquemaTarifarioPasajes",[GestionPasajesController::class,"formEsquemaTarifario"])->name("esquemaTarifario.index");
            Route::post("/esquemaTarifarioPasajes/filtrar",[GestionPasajesController::class,"filtrarEsquemaTarifario"])->name("esquemaTarifario.filtrar");
            Route::get("/esquemaTarifarioPasajes/listaDatos",[GestionPasajesController::class,"listaEsquemaTarifario"])->name("esquemaTarifario.listaDatos");
            Route::get("/esquemaTarifarioPasajes/cargarData",[GestionPasajesController::class,"cargarDataEsquemaTarifario"])->name("esquemaTarifario.cargarData");
        });

        //Reportes Carga
        Route::prefix("carga")->group(function(){
            Route::get("/",[ReportesController::class,"reportesCarga"])->name("reportes.carga");
        });
    });
});