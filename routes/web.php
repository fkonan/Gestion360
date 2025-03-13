<?php

use App\Http\Controllers\DepartamentoController;
use App\Http\Controllers\FormatoController;
use App\Http\Controllers\ModuloController;
use App\Http\Controllers\PermisosController;
use App\Http\Controllers\PersonaController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/home', function () {
    return view('home');
})->middleware('auth')->name('home');

Route::prefix("departamentos")->middleware('auth')->group(function(){
    Route::get("/",[DepartamentoController::class,"index"])->name("departamentos.index");
    Route::get("/create",[DepartamentoController::class,"create"])->name("departamentos.create");
    Route::get("/{id}",[DepartamentoController::class,"show"])->name("departamentos.show");
    Route::get("/edit/{id}",[DepartamentoController::class,"edit"])->name("departamentos.edit");
    Route::get("/municipios/{id}",[DepartamentoController::class,"getMunici"])->name("departamentos.municipios");
    Route::post("/",[DepartamentoController::class,"store"])->name("departamentos.store");
    Route::delete("/{id}",[DepartamentoController::class,"destroy"])->name("departamentos.destroy");
    Route::put("/{id}",[DepartamentoController::class,"update"])->name("departamentos.update");
});


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
        Route::resource("permisos", PermisosController::class)->only(["edit", "update"]);
    });
});

//Rutas Modulo Configuracion
Route::prefix("configuracion")->middleware(['auth', 'permisos:acceso-configuracion'])->group(function(){
    Route::prefix("sistema")->group(function(){
        Route::prefix("modulos")->name("modulos.")->group(function(){
            Route::get("/",[ModuloController::class,"index"])->name("index");
            Route::get("/create",[ModuloController::class,"create"])->middleware('permisos:crear-gestion-modulos')->name("create");
            Route::post("/",[ModuloController::class,"store"])->name("store");
            Route::get("/{id}",[ModuloController::class,"edit"])->middleware('permisos:editar-gestion-modulos')->name("edit");
            Route::put("/{id}",[ModuloController::class,"update"])->name("update");
        });
    });
});


require __DIR__.'/auth.php';


