<?php

use App\Http\Controllers\FormatoController;
use App\Http\Controllers\IncapacidadController;
use App\Http\Controllers\ModuloController;
use App\Http\Controllers\PermisosController;
use App\Http\Controllers\PersonaController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/home', function () {
    return view('home');
})->middleware('auth')->name('home');


Route::prefix("formatos")->middleware(['auth', 'permisos:acceso-gestion-documental'])->name("formatos.")->group(function(){
    Route::get("/",[FormatoController::class,"index"])->name("index");
    Route::get("/create",[FormatoController::class,"crearNuevoFormato"])->middleware('permisos:crear-gestion-documental')->name("create");
    Route::post("/",[FormatoController::class,"guardarFormato"])->name("store");
    
    Route::prefix("/{id}/versions")->middleware('permisos:editar-gestion-documental')->name("versions.")->group(function(){
        Route::get("/",[FormatoController::class,"versionesFormato"])->name("index");
        Route::get("/create",[FormatoController::class,"crearVersionFormato"])->name("create");
        Route::post("/",[FormatoController::class,"guardarVersionFormato"])->name("store");
    });
});

//Ruta Modulo administración
Route::prefix("administracion")->middleware(['auth', 'permisos:acceso-administracion'])->group(function(){
    //Resources -> index, create, store, edit, update, delete, show
    Route::resource("personas",PersonaController::class)->except(["destroy"]);
    Route::resource("usuarios",UserController::class)->except(["show","destroy"]);
    Route::prefix("usuarios")->group(function(){
        Route::get("/roles/{id}",[RolController::class,"editRolUsuario"])->name("roles.edit");
        Route::put("/roles/{id}",[RolController::class,"updateRolUsuario"])->name("roles.update");
        Route::get("/permisos/{id}",[PermisosController::class,"edit"])->name("permisos.edit");
        Route::put("/permisos/{id}",[PermisosController::class,"update"])->name("permisos.update");
    });
});

//Rutas Modulo Configuracion
Route::prefix("configuracion")->middleware(['auth', 'permisos:acceso-configuracion'])->group(function(){
    Route::prefix("sistema")->group(function(){
        Route::get("/",[ModuloController::class,"getGestionSistema"])->name("gestion-sistema.index");
        Route::prefix("modulos")->name("modulos.")->group(function(){
            Route::get("/",[ModuloController::class,"index"])->name("index");
            Route::get("/create",[ModuloController::class,"create"])->middleware('permisos:crear-gestion-modulos')->name("create");
            Route::get("/{id}",[ModuloController::class,"edit"])->middleware('permisos:editar-gestion-modulos')->name("edit");
            Route::post("/",[ModuloController::class,"store"])->name("store");
            Route::put("/{id}",[ModuloController::class,"update"])->name("update");
        });
    });
});

//Rutas Modulo Gestion RRHH
Route::prefix("gestionRRHH")->middleware(['auth', 'permisos:acceso-gestionRRHH'])->group(function(){
    Route::prefix("gestion-empleado")->name("gestion-incapacidades.")->group(function(){
        Route::get("/",[ModuloController::class,"getGestionEmpleado"])->name("index");
        Route::get("/incapacidades",[IncapacidadController::class,"listaIncapacidades"])->name("incapacidades");
        Route::get("/seguimiento",[IncapacidadController::class,"incapacidadesSeguimiento"])->name("seguimiento");
    });
});


require __DIR__.'/auth.php';


