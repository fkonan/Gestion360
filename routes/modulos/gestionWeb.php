<?php

use App\Constants\Permisos;
use App\Http\Controllers\AppmovilController;
use App\Http\Controllers\GestionWebController;
use Illuminate\Support\Facades\Route;

//Rutas Modulo Gestion Web
Route::prefix("gestion-web")->middleware(['auth','permisos:'.Permisos::GESTION_WEB_ACCEDER,'modulo.activo:14'])->group(function(){ 
    Route::get("/chatbot",[GestionWebController::class,"loginChatBot"])->name("chatbot.index");

    //Submodulo gestion appmovil
    Route::prefix("gestion-appmovil")->middleware(['submodulo.activo:27','permisos:'.Permisos::GESTION_WEB_GESTION_APP_MOVIL_ACCEDER])->group(function(){
        //index
        Route::get("/",[AppmovilController::class,"indexGestionMovil"])->name("gestion-appmovil.index");

        //Notificaciones
        Route::prefix("notificaciones")->name("notificaciones.")->group(function(){
            Route::get("/",[AppmovilController::class,"notificaciones"])->name("index");
            Route::get("/crear",[AppmovilController::class,"crearNotificacion"])->name("create");
            Route::post("/registrar",[AppmovilController::class,"registrarNotificacion"])->name("registrar");
            Route::get("/usuarios/buscar", [AppmovilController::class, 'usuariosConAppmovil'])->name('usuarios-disponibles');
            Route::get("/cargarDatos",[AppmovilController::class,"cargarNotificaciones"])->middleware('soloAJAX')->name("cargarDatos");
            Route::post("/{id}/cambiar-estado", [AppmovilController::class, "cambiarEstadoNotificacion"])->middleware('soloAJAX')->name("cambiarEstado");
            Route::get("/{id}/edit",[AppmovilController::class,"editarNotificacion"])->name("edit");
            Route::put("/{id}",[AppmovilController::class,"updateNotificacion"])->name("update");
        });
    });
});