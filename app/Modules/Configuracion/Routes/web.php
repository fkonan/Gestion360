<?php

use App\Constants\Permisos;
use App\Modules\Administration\Http\Controllers\RolController;
use App\Modules\Configuracion\Http\Controllers\ModuloController;
use App\Modules\Configuracion\Http\Controllers\PermisosController;
use App\Modules\Configuracion\Http\Controllers\SubModuloController;
use Illuminate\Support\Facades\Route;

// Rutas Modulo Configuracion
Route::prefix('configuracion')->middleware(['auth', 'permisos:'.Permisos::CONFIGURACION_ACCEDER, 'modulo.activo:11'])->group(function () {
    Route::prefix('sistema')->middleware(['permisos:configuracion.gestion_sistema.acceder', 'submodulo.activo:19'])->group(function () {
        Route::get('/', [ModuloController::class, 'getGestionSistema'])->name('gestion-sistema.index');

        Route::prefix('modulos')->name('modulos.')->group(function () {
            Route::get('/', [ModuloController::class, 'index'])->name('index');
            Route::get('/cargarDatos', [ModuloController::class, 'cargarDatos'])->middleware('soloAJAX')->name('cargarDatos');
            Route::get('/create', [ModuloController::class, 'create'])->middleware('soloAJAX')->name('create');
            Route::get('/{id}', [ModuloController::class, 'edit'])->middleware('soloAJAX')->name('edit');
            Route::post('/', [ModuloController::class, 'store'])->name('store');
            Route::put('/{id}', [ModuloController::class, 'update'])->name('update');
            Route::post('/{id}/cambiar-estado', [ModuloController::class, 'cambiarEstado'])->middleware('soloAJAX')->name('cambiarEstado');
        });

        Route::prefix('submodulos')->name('submodulos.')->group(function () {
            Route::get('/', [SubModuloController::class, 'index'])->name('index');
            Route::get('/cargarDatos', [SubModuloController::class, 'cargarDatos'])->middleware('soloAJAX')->name('cargarDatos');
            Route::get('/create', [SubModuloController::class, 'create'])->middleware('soloAJAX')->name('create');
            Route::post('/', [SubModuloController::class, 'store'])->name('store');
            Route::get('/{id}', [SubModuloController::class, 'edit'])->middleware('soloAJAX')->name('edit');
            Route::put('/{id}', [SubModuloController::class, 'update'])->name('update');
            Route::post('/{id}/cambiar-estado', [SubModuloController::class, 'cambiarEstado'])->middleware('soloAJAX')->name('cambiarEstado');
        });

        Route::prefix('roles')->name('roles.')->group(function () {
            Route::get('/', [RolController::class, 'index'])->name('index');
            Route::get('/create', [RolController::class, 'create'])->name('create');
            Route::post('/', [RolController::class, 'store'])->name('store');
            Route::get('/{id}/permisos', [RolController::class, 'permisosRol'])->name('permisos');
            Route::put('/{id}/permisos', [RolController::class, 'updatePermisos'])->name('permisos.update');
        });

        Route::prefix('administracion-permisos')->name('gestion-permisos.')->group(function () {
            Route::get('/', [PermisosController::class, 'index'])->name('index');
            Route::get('/cargarDatos', [PermisosController::class, 'cargarDatos'])->middleware('soloAJAX')->name('cargarDatos');
            Route::get('/{id}', [PermisosController::class, 'edit'])->middleware('soloAJAX')->name('edit');
            Route::put('/{id}', [PermisosController::class, 'update'])->name('update');
        });
    });
});
