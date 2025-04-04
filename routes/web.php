<?php

use App\Http\Controllers\FormatoController;
use App\Http\Controllers\IncapacidadController;
use App\Http\Controllers\ModuloController;
use App\Http\Controllers\PermisosController;
use App\Http\Controllers\PersonaController;
use App\Http\Controllers\RolController;
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
    return "Cleared!";
 });


Route::prefix("formatos")->middleware(['auth', 'permisos:acceso-gestion-documental','modulo.activo:21'])->name("formatos.")->group(function(){
    Route::get("/",[FormatoController::class,"index"])->name("index");
    Route::get("/create",[FormatoController::class,"crearNuevoFormato"])->name("create");
    Route::post("/",[FormatoController::class,"guardarFormato"])->name("store");
    
    Route::prefix("/{id}/versions")->name("versions.")->group(function(){
        Route::get("/",[FormatoController::class,"versionesFormato"])->name("index");
        Route::get("/create",[FormatoController::class,"crearVersionFormato"])->name("create");
        Route::post("/",[FormatoController::class,"guardarVersionFormato"])->name("store");
    });
});

//Ruta Modulo administración
Route::prefix("administracion")->middleware(['auth', 'permisos:acceso-administracion','modulo.activo:7'])->group(function(){
    //Resources -> index, create, store, edit, update, delete, show
    Route::resource("personas",PersonaController::class)->except(["destroy"]);
    Route::resource("usuarios",UserController::class)->except(["show","destroy"]);
    Route::get("usuarios/cargarDatos",[UserController::class,"cargarDatos"])->name("usuarios.cargarDatos");

    Route::prefix("usuarios")->group(function(){
        Route::get("/roles/{id}",[RolController::class,"editRolUsuario"])->name("roles.edit");
        Route::put("/roles/{id}",[RolController::class,"updateRolUsuario"])->name("roles.update");
        Route::get("/permisos/{id}",[PermisosController::class,"edit"])->name("permisos.edit");
        Route::put("/permisos/{id}",[PermisosController::class,"update"])->name("permisos.update");
    });

    Route::prefix("reportes")->group(function(){
        Route::get("/",[ModuloController::class,"getReportes"])->name("reportes.index");
    });
});

//Rutas Modulo Configuracion
Route::prefix("configuracion")->middleware(['auth', 'permisos:acceso-configuracion','modulo.activo:6'])->group(function(){
    Route::prefix("sistema")->group(function(){
        Route::get("/",[ModuloController::class,"getGestionSistema"])->name("gestion-sistema.index");
        Route::prefix("modulos")->name("modulos.")->group(function(){
            Route::get("/",[ModuloController::class,"index"])->name("index");
            Route::get("/create",[ModuloController::class,"create"])->name("create");
            Route::get("/{id}",[ModuloController::class,"edit"])->name("edit");
            Route::post("/",[ModuloController::class,"store"])->name("store");
            Route::put("/{id}",[ModuloController::class,"update"])->name("update");
        });
    });
});

//Rutas Modulo Gestion RRHH
Route::prefix("gestionRRHH")->middleware(['auth', 'permisos:acceso-gestion-rh','modulo.activo:2'])->group(function(){
    Route::prefix("gestion-empleado")->name("gestion-incapacidades.")->group(function(){
        Route::get("/",[ModuloController::class,"getGestionEmpleado"])->name("index");
        Route::get("/incapacidades",[IncapacidadController::class,"listaIncapacidades"])->name("incapacidades");
        Route::get("/incapacidades/cargarDatos",[IncapacidadController::class,"cargarDatos"])->name("incapacidades.cargarDatos");
        Route::get("/incapacidades/{id}/datos",[IncapacidadController::class,"editIncapacidad"])->name("incapacidades.edit");
        Route::put("/incapacidades/{id}/datos",[IncapacidadController::class,"updateIncapacidad"])->name("incapacidades.update");
        Route::get("/incapacidades/{id}/gestion",[IncapacidadController::class,"gestionIncapacidad"])->name("incapacidades.gestion");
        Route::put("/incapacidades/{id}/estado",[IncapacidadController::class,"updateEstadoIncapacidad"])->name("incapacidades.estado");
        Route::get("/seguimiento",[IncapacidadController::class,"incapacidadesSeguimiento"])->name("seguimiento");
        Route::get("/seguimiento/cargarDatos",[IncapacidadController::class,"cargarDatosSeguimiento"])->name("seguimiento.cargarDatos");
        Route::get("/seguimiento/{id}/adjuntos",[IncapacidadController::class,"incapacidadAdjuntos"])->name("seguimiento.adjuntos");
        Route::get("/seguimiento/{id}/registro",[IncapacidadController::class,"seguimientoDetalle"])->name("seguimiento.detalle");
        Route::get("/seguimiento/{id}/nuevo-seguimiento",[IncapacidadController::class,"nuevoSeguimiento"])->name("seguimiento.detalle.crear");
        Route::post("/seguimiento/{id}/nuevo-seguimiento",[IncapacidadController::class,"guardarSeguimiento"])->name("seguimiento.detalle.store");
    });
});


require __DIR__.'/auth.php';


