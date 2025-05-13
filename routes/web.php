<?php

use App\Http\Controllers\IncapacidadController;
use App\Http\Controllers\ModuloController;
use App\Http\Controllers\PermisosController;
use App\Http\Controllers\PersonaController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\SeguimientoIncapacidadController;
use App\Http\Controllers\SubModuloController;
use App\Http\Controllers\TiquetesImpresosController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Route::get('/home', function () {
    return view('home');
})->middleware('auth')->name('home');

Route::get('/clear', function () {
    Artisan::call('storage:link');
    Artisan::call('cache:clear');
    Artisan::call('config:cache');
    Artisan::call('view:clear');
    Artisan::call('route:cache');
    return "Cleared!";
});
 
//Ruta Modulo administración
Route::prefix("administracion")->middleware(['auth', 'permisos:acceso-administracion','modulo.activo:7'])->group(function(){
    Route::prefix("personas")->group(function(){
        Route::get("/",[PersonaController::class,"index"])->name("personas.index");
        Route::get("/crear",[PersonaController::class,"create"])->name("personas.create");
        Route::post("/",[PersonaController::class,"store"])->name("personas.store");
        Route::get("/{id}/edit",[PersonaController::class,"edit"])->name("personas.edit");
        Route::put("/{id}",[PersonaController::class,"update"])->name("personas.update");
    });
    Route::prefix("usuarios")->group(function(){
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
    });
    Route::prefix("reportes")->middleware('submodulo.activo:12')->group(function(){
        Route::get("/",[ModuloController::class,"getReportes"])->name("reportes.index");
        Route::get("/impresionTiquetes",[TiquetesImpresosController::class,"fechasReporte"])->middleware('soloAJAX')->name("reportes.tiquetes");
        Route::post("/impresionTiquetes/filtrar",[TiquetesImpresosController::class,"filtrarTiquetes"])->name("reportes.filtrarTiquetes");
        Route::get("/impresionTiquetes/listaTiquetes",[TiquetesImpresosController::class,"listaTiquetes"])->name("reportes.listaTiquetes");
        Route::get("/impresionTiquetes/cargarData",[TiquetesImpresosController::class,"cargarDataTiquetes"])->middleware('soloAJAX')->name("reportes.cargarData");
    });
});

//Rutas Modulo Configuracion
Route::prefix("configuracion")->middleware(['auth', 'permisos:acceso-configuracion','modulo.activo:6'])->group(function(){
    Route::prefix("sistema")->group(function(){
        Route::get("/",[ModuloController::class,"getGestionSistema"])->name("gestion-sistema.index");

        Route::prefix("modulos")->name("modulos.")->group(function(){
            Route::get("/",[ModuloController::class,"index"])->name("index");
            Route::get("/create",[ModuloController::class,"create"])->middleware('soloAJAX')->name("create");
            Route::get("/{id}",[ModuloController::class,"edit"])->middleware('soloAJAX')->name("edit");
            Route::post("/",[ModuloController::class,"store"])->name("store");
            Route::put("/{id}",[ModuloController::class,"update"])->name("update");
        });

        Route::prefix("submodulos")->name("submodulos.")->group(function(){
            Route::get("/",[SubModuloController::class,"index"])->name("index");
            Route::get("/create",[SubModuloController::class,"create"])->middleware('soloAJAX')->name("create");
            Route::post("/",[SubModuloController::class,"store"])->name("store");
            Route::get("/{id}",[SubModuloController::class,"edit"])->middleware('soloAJAX')->name("edit");
            Route::put("/{id}",[SubModuloController::class,"update"])->name("update");
        });

        Route::prefix("roles")->name("roles.")->group(function(){
            Route::get("/",[RolController::class,"index"])->name("index");
            Route::get("/create",[RolController::class,"create"])->middleware('soloAJAX')->name("create");
            Route::post("/",[RolController::class,"store"])->name("store");
            Route::get("/{id}/permisos",[RolController::class,"permisosRol"])->middleware('soloAJAX')->name("permisos");
            Route::put("/{id}/permisos",[RolController::class,"updatePermisos"])->name("permisos.update");
        });
    });
});

//Rutas Modulo Gestion RRHH
Route::prefix("gestionRRHH")->middleware(['auth', 'permisos:acceso-gestion-rh','modulo.activo:2'])->group(function(){
    Route::prefix("gestion-empleado")->middleware('submodulo.activo:7')->name("gestion-incapacidades.")->group(function(){
        Route::get("/",[ModuloController::class,"getGestionEmpleado"])->name("index");
        Route::get("/incapacidades",[IncapacidadController::class,"listaIncapacidades"])->name("incapacidades");
        Route::get("/incapacidades/cargarDatos",[IncapacidadController::class,"cargarDatos"])->middleware('soloAJAX')->name("incapacidades.cargarDatos");
        Route::get("/incapacidades/{id}/datos",[IncapacidadController::class,"editIncapacidad"])->middleware('soloAJAX')->name("incapacidades.edit");
        Route::put("/incapacidades/{id}/datos",[IncapacidadController::class,"updateIncapacidad"])->name("incapacidades.update");
        Route::get("/incapacidades/{id}/gestion",[IncapacidadController::class,"gestionIncapacidad"])->middleware('soloAJAX')->name("incapacidades.gestion");
        Route::put("/incapacidades/{id}/estado",[IncapacidadController::class,"updateEstadoIncapacidad"])->name("incapacidades.estado");

        //Esta ruta carga adjuntos para la incapacidad y para el seguimiento
        Route::get("/seguimiento/{id}/adjuntos",[IncapacidadController::class,"incapacidadAdjuntos"])->middleware('soloAJAX')->name("seguimiento.adjuntos");

        Route::get("/seguimiento",[SeguimientoIncapacidadController::class,"incapacidadesSeguimiento"])->name("seguimiento");
        Route::get("/seguimiento/cargarDatos",[SeguimientoIncapacidadController::class,"cargarDatosSeguimiento"])->middleware('soloAJAX')->name("seguimiento.cargarDatos");
        Route::get("/seguimiento/{id}/registro",[SeguimientoIncapacidadController::class,"seguimientoDetalle"])->name("seguimiento.detalle");
        Route::get("/seguimiento/{id}/nuevo-seguimiento",[SeguimientoIncapacidadController::class,"nuevoSeguimiento"])->name("seguimiento.detalle.crear");
        Route::post("/seguimiento/{id}/nuevo-seguimiento",[SeguimientoIncapacidadController::class,"guardarSeguimiento"])->name("seguimiento.detalle.store");
    });
});


require __DIR__.'/auth.php';


