<?php

use App\Http\Controllers\DepartamentoController;
use App\Http\Controllers\FormatoController;
use App\Http\Controllers\PersonaController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name("main");

Route::prefix("departamentos")->group(function(){
    Route::get("/",[DepartamentoController::class,"index"])->name("departamentos.index");
    Route::get("/form",[DepartamentoController::class,"create"])->name("departamentos.create");
    Route::get("/{id}",[DepartamentoController::class,"show"])->name("departamentos.show");
    Route::get("/edit/{id}",[DepartamentoController::class,"edit"])->name("departamentos.edit");
    Route::get("/municipios/{id}",[DepartamentoController::class,"getMunici"])->name("departamentos.municipios");

    Route::post("/",[DepartamentoController::class,"store"])->name("departamentos.store");
   
    Route::delete("/{id}",[DepartamentoController::class,"destroy"])->name("departamentos.destroy");
    Route::put("/{id}",[DepartamentoController::class,"update"])->name("departamentos.update");
});

Route::prefix("personas")->group(function(){
    Route::get("/",[PersonaController::class,"index"])->name("persona.index");
    Route::get("/form",[PersonaController::class,"create"])->name("persona.create");
    Route::get("/{id}",[PersonaController::class,"show"])->name("persona.show");   
    Route::get("/edit/{id}",[PersonaController::class,"edit"])->name("persona.edit"); 

    Route::post("/",[PersonaController::class,"store"])->name("persona.store");
    
    Route::put("/{id}",[PersonaController::class,"update"])->name("persona.update");
    
});

Route::get("/pdfCreate/{name}",[FormatoController::class,"llenarFormatoPDF"])->name("formato.llenarFormatoPDF");

Route::prefix("formato")->group(function(){
    Route::get("/",[FormatoController::class,"index"])->name("formato.index");
    Route::get("/form",[FormatoController::class,"crearNuevoFormato"])->name("formato.crearNuevoFormato");
    Route::get("/{id}/versiones/",[FormatoController::class,"versionesFormato"])->name("formato.listaVersiones");
    Route::post("/",[FormatoController::class,"guardarFormato"])->name("formato.guardarFormato");
    Route::get("/newversion/{id}",[FormatoController::class,"crearVersionFormato"])->name("formato.crearVersionFormato");
    Route::post("/newversion",[FormatoController::class,"guardarVersionFormato"])->name("formato.guardarVersionFormato");
});


