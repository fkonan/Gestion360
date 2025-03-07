<?php

use App\Http\Controllers\DepartamentoController;
use App\Http\Controllers\FormatoController;
use App\Http\Controllers\ModuloController;
use App\Http\Controllers\PersonaController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;


Route::get('/', function () {
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

Route::prefix("personas")->middleware('auth')->group(function(){
    Route::get("/",[PersonaController::class,"index"])->name("admin.personas");
    Route::get("/create",[PersonaController::class,"create"])->name("persona.create");
    Route::get("/{id}",[PersonaController::class,"show"])->name("persona.show");   
    Route::get("/edit/{id}",[PersonaController::class,"edit"])->name("persona.edit"); 

    Route::post("/",[PersonaController::class,"store"])->name("persona.store");
    Route::post("/{id}",[PersonaController::class,"update"])->name("persona.update");
});

Route::prefix("usuarios")->middleware('auth')->group(function(){
    Route::get("/",[UserController::class,"index"])->name("admin.usuarios");
    Route::get("/create",[UserController::class,"crearNuevoUsuario"])->name("usuarios.crearNuevoUsuario");
});


Route::prefix("formatos")->middleware('auth')->name("formatos.")->group(function(){
    Route::get("/",[FormatoController::class,"index"])->name("index");
    Route::get("/create",[FormatoController::class,"crearNuevoFormato"])->name("create");
    Route::post("/",[FormatoController::class,"guardarFormato"])->name("store");
    
    Route::prefix("/{id}/versions")->middleware('auth')->name("versions.")->group(function(){
        Route::get("/",[FormatoController::class,"versionesFormato"])->name("index");
        Route::get("/create",[FormatoController::class,"crearVersionFormato"])->name("create");
        Route::post("/",[FormatoController::class,"guardarVersionFormato"])->name("store");
    });
});


//Rutas Modulo Configuracion
Route::prefix("configuracion")->middleware('auth')->name("configuracion.")->group(function(){

    Route::prefix("sistema")->middleware('auth')->name("sistema.")->group(function(){

        Route::prefix("modulos")->middleware('auth')->name("modulos.")->group(function(){
            Route::get("/",[ModuloController::class,"index"])->name("index");
            Route::get("/create",[ModuloController::class,"create"])->name("create");
            Route::post("/",[ModuloController::class,"store"])->name("store");
        });
       
    });

});


require __DIR__.'/auth.php';


