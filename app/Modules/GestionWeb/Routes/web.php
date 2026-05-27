<?php

use App\Constants\Permisos;
use App\Modules\GestionWeb\Http\Controllers\AppmovilController;
use App\Modules\GestionWeb\Http\Controllers\GestionWebController;
use App\Modules\GestionWeb\Http\Controllers\PersonaAppmovilController;
use App\Modules\GestionWeb\Http\Controllers\RecursosDigitalesAdminController;
use App\Modules\GestionWeb\Http\Controllers\TrackingRemesasController;
use Illuminate\Support\Facades\Route;

// Tracking remesas (público)
Route::get('/tracking-remesas', function () {
    return view('gestionweb::remesas.trackingRemesas');
})->name('trackingRemesas.index');

Route::post('/tracking-remesas/consultar', [TrackingRemesasController::class, 'consultar'])->name('trackingRemesas.consultar');

// Rutas Modulo Gestion Web
Route::prefix('gestion-web')->middleware(['auth', 'permisos:'.Permisos::GESTION_WEB_ACCEDER, 'modulo.activo:14'])->group(function () {
    Route::get('/chatbot', [GestionWebController::class, 'loginChatBot'])->name('chatbot.index');

    // Submodulo gestion appmovil
    Route::prefix('gestion-appmovil')->middleware(['submodulo.activo:27', 'permisos:'.Permisos::GESTION_WEB_GESTION_APP_MOVIL_ACCEDER])->group(function () {
        // index
        Route::get('/', [AppmovilController::class, 'indexGestionMovil'])->name('gestion-appmovil.index');

        // Notificaciones
        Route::prefix('notificaciones')->name('notificaciones.')->group(function () {
            Route::get('/', [AppmovilController::class, 'notificaciones'])->name('index');
            Route::get('/crear', [AppmovilController::class, 'crearNotificacion'])->name('create');
            Route::post('/registrar', [AppmovilController::class, 'registrarNotificacion'])->name('registrar');
            Route::get('/usuarios/buscar', [AppmovilController::class, 'usuariosConAppmovil'])->name('usuarios-disponibles');
            Route::get('/cargarDatos', [AppmovilController::class, 'cargarNotificaciones'])->middleware('soloAJAX')->name('cargarDatos');
            Route::post('/{id}/cambiar-estado', [AppmovilController::class, 'cambiarEstadoNotificacion'])->middleware('soloAJAX')->name('cambiarEstado');
            Route::get('/{id}/edit', [AppmovilController::class, 'editarNotificacion'])->name('edit');
            Route::put('/{id}', [AppmovilController::class, 'updateNotificacion'])->name('update');
        });

        Route::prefix('personas')->name('personas-appmovil.')->group(function () {
            Route::get('/', [PersonaAppmovilController::class, 'index'])->name('index');
            Route::get('/cargarDatos', [PersonaAppmovilController::class, 'cargarDatos'])->middleware('soloAJAX')->name('cargarDatos');
            Route::get('/{id}/edit', [PersonaAppmovilController::class, 'edit'])->name('edit');
            Route::put('/{id}', [PersonaAppmovilController::class, 'update'])->name('update');
        });

        Route::prefix('recursos-digitales')->name('recursos-digitales.')->group(function () {
            Route::get('/', [RecursosDigitalesAdminController::class, 'index'])->name('index');
            Route::get('/tipo/{tipo}', [RecursosDigitalesAdminController::class, 'show'])->name('show');
            Route::get('/tipo/{tipo}/crear', [RecursosDigitalesAdminController::class, 'create'])->name('create');
            Route::post('/tipo/{tipo}', [RecursosDigitalesAdminController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [RecursosDigitalesAdminController::class, 'edit'])->name('edit');
            Route::put('/{id}', [RecursosDigitalesAdminController::class, 'update'])->name('update');
            Route::post('/{id}/cambiar-estado', [RecursosDigitalesAdminController::class, 'cambiarEstado'])->middleware('soloAJAX')->name('cambiarEstado');
        });
    });
});
