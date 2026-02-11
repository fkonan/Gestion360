<?php

use App\Modules\RadFact\Http\Controllers\AprobacionController;
use App\Modules\RadFact\Http\Controllers\AreaController;
use App\Modules\RadFact\Http\Controllers\ProveedorController;
use App\Modules\RadFact\Http\Controllers\RadicacionController;
use App\Modules\RadFact\Http\Controllers\SubgerenciaComprasController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas del Módulo Radicación de Facturas
|--------------------------------------------------------------------------
|
| Estas rutas manejan el flujo completo de radicación de facturas:
| - Gestión de proveedores y áreas
| - Radicación de facturas con distribuciones
| - Aprobación por áreas
| - Aprobación de subgerencia y compras
|
*/

// Grupo de rutas con middleware de autenticación
Route::middleware(['auth'])->prefix('radicacion-facturas')->name('radfact.')->group(function () {

    // Dashboard/Home del módulo
    Route::get('/', function () {
        return redirect()->route('radfact.radicaciones.index');
    })->name('home');

    // ========== PROVEEDORES ==========
    Route::prefix('proveedores')->name('proveedores.')->group(function () {
        Route::get('/', [ProveedorController::class, 'index'])->name('index');
        Route::get('/crear', [ProveedorController::class, 'create'])->name('create');
        Route::post('/', [ProveedorController::class, 'store'])->name('store');
        Route::get('/{proveedor}', [ProveedorController::class, 'show'])->name('show');
        Route::get('/{proveedor}/editar', [ProveedorController::class, 'edit'])->name('edit');
        Route::put('/{proveedor}', [ProveedorController::class, 'update'])->name('update');

        // AJAX
        Route::get('/api/cargar-datos', [ProveedorController::class, 'cargarDatos'])->name('cargarDatos');
        Route::get('/api/buscar-documento', [ProveedorController::class, 'buscarPorDocumento'])->name('buscar_documento');
    });

    // ========== ÁREAS ==========
    Route::prefix('areas')->name('areas.')->group(function () {
        Route::get('/', [AreaController::class, 'index'])->name('index');
        Route::get('/crear', [AreaController::class, 'create'])->name('create');
        Route::post('/', [AreaController::class, 'store'])->name('store');
        Route::get('/{area}/editar', [AreaController::class, 'edit'])->name('edit');
        Route::put('/{area}', [AreaController::class, 'update'])->name('update');

        // AJAX
        Route::get('/api/cargar-datos', [AreaController::class, 'cargarDatos'])->name('cargarDatos');
        Route::get('/api/listar', [AreaController::class, 'listar'])->name('listar');
    });

    // ========== RADICACIONES ==========
    Route::prefix('radicaciones')->name('radicaciones.')->group(function () {
        Route::get('/', [RadicacionController::class, 'index'])->name('index');
        Route::get('/crear', [RadicacionController::class, 'create'])->name('create');
        Route::post('/', [RadicacionController::class, 'store'])->name('store');
        Route::get('/{radicacion}', [RadicacionController::class, 'show'])->name('show');

        // Ajustar distribuciones (cuando hay rechazos)
        Route::get('/{radicacion}/editar-distribuciones', [RadicacionController::class, 'editDistribuciones'])
            ->name('edit_distribuciones');
        Route::put('/{radicacion}/actualizar-distribuciones', [RadicacionController::class, 'updateDistribuciones'])
            ->name('update_distribuciones');

        // AJAX
        Route::get('/api/cargar-datos', [RadicacionController::class, 'cargarDatos'])->name('cargarDatos');
    });

    // ========== APROBACIONES (ÁREAS) ==========
    Route::prefix('aprobaciones')->name('aprobaciones.')->group(function () {
        Route::get('/', [AprobacionController::class, 'index'])->name('index');
        Route::get('/{aprobacion}', [AprobacionController::class, 'show'])->name('show');
        Route::post('/{aprobacion}/aprobar', [AprobacionController::class, 'aprobar'])->name('aprobar');
        Route::post('/{aprobacion}/rechazar', [AprobacionController::class, 'rechazar'])->name('rechazar');

        // Historial
        Route::get('/historial/mis-aprobaciones', [AprobacionController::class, 'historial'])->name('historial');
    });

    // ========== SUBGERENCIA ==========
    Route::prefix('subgerencia')->name('subgerencia.')->group(function () {
        Route::get('/', [SubgerenciaComprasController::class, 'indexSubgerencia'])->name('index');
        Route::get('/{radicacion}', [SubgerenciaComprasController::class, 'showSubgerencia'])->name('show');
        Route::post('/{radicacion}/aprobar', [SubgerenciaComprasController::class, 'aprobarSubgerencia'])->name('aprobar');
        Route::post('/{radicacion}/rechazar', [SubgerenciaComprasController::class, 'rechazarSubgerencia'])->name('rechazar');
    });

    // ========== COMPRAS ==========
    Route::prefix('compras')->name('compras.')->group(function () {
        Route::get('/', [SubgerenciaComprasController::class, 'indexCompras'])->name('index');
        Route::get('/{radicacion}', [SubgerenciaComprasController::class, 'showCompras'])->name('show');
        Route::post('/{radicacion}/aprobar', [SubgerenciaComprasController::class, 'aprobarCompras'])->name('aprobar');
        Route::post('/{radicacion}/rechazar', [SubgerenciaComprasController::class, 'rechazarCompras'])->name('rechazar');
    });
});
