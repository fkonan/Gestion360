<?php

use App\Constants\Permisos;
use App\Modules\SIG\Http\Controllers\MapaProcesosController;
use Illuminate\Support\Facades\Route;

// Rutas SIG
Route::prefix('sig')->middleware(['auth', 'permisos:'.Permisos::SIG_ACCEDER, 'modulo.activo:18'])->group(function () {

    Route::get('/', function () {
        return view('sig::mapa_procesos_inicio');
    })->name('mapaProcesos.index');

    // Entrada principal del mapa
    Route::get('/mapa-procesos', function () {
        return view('sig::mapa_procesos_inicio');
    })->name('mapa-procesos.index');

    // Vista principal de mapa (solo consulta)
    Route::get('/mapa-procesos/{categoria}', [MapaProcesosController::class, 'porCategoria'])
        ->name('mapa-procesos.categoria');

    Route::get('/mapa-procesos/documento/{id}/emisiones', [MapaProcesosController::class, 'emisiones'])
        ->middleware('permisos:'.Permisos::SIG_MAPA_PROCESOS_VER_EMISION)
        ->name('mapa-procesos.documento.emisiones');

    // Emisiones devueltas para el creador
    Route::get('/mapa-procesos/emisiones/devueltas', [MapaProcesosController::class, 'emisionesDevueltasUsuario'])
        ->middleware('permisos:'.Permisos::SIG_MAPA_PROCESOS_VER_EMISION)
        ->name('mapa-procesos.emisiones.devueltas');

    // Acciones de emision (crear)
    Route::middleware('permisos:'.Permisos::SIG_MAPA_PROCESOS_CREAR_EMISION)->group(function () {
        Route::get('/mapa-procesos/documento/{id}/emision', [MapaProcesosController::class, 'crearEmision'])
            ->name('mapa-procesos.documento.emision.nueva');

        Route::post('/mapa-procesos/documento/{id}/emision', [MapaProcesosController::class, 'guardarEmision'])
            ->name('mapa-procesos.documento.emision.guardar');
    });

    // Acciones de documento (editar)
    Route::middleware('permisos:'.Permisos::SIG_MAPA_PROCESOS_EDITAR)->group(function () {
        Route::get('/mapa-procesos/documento/{id}/editar', [MapaProcesosController::class, 'editarDocumento'])
            ->name('mapa-procesos.documento.editar');

        Route::post('/mapa-procesos/documento/{id}/actualizar', [MapaProcesosController::class, 'actualizarDocumento'])
            ->name('mapa-procesos.documento.actualizar');
    });

    // Nuevo documento
    Route::middleware('permisos:'.Permisos::SIG_MAPA_PROCESOS_CREAR)->group(function () {
        Route::get('/mapa-procesos/documento/nuevo', [MapaProcesosController::class, 'crearDocumentoNuevo'])
            ->name('mapa-procesos.documento.nuevo');

        Route::post('/mapa-procesos/documento', [MapaProcesosController::class, 'guardarDocumentoNuevo'])
            ->name('mapa-procesos.documento.guardar');
    });

    // Acciones de administracion (aprobar / devolver / rechazar / estado / pendientes / admin)
    Route::middleware('permisos:'.Permisos::SIG_MAPA_PROCESOS_ACCEDER)->group(function () {
        Route::get('/mapa-procesos/emisiones/pendientes', [MapaProcesosController::class, 'emisionesPendientes'])
            ->name('mapa-procesos.emisiones.pendientes');

        Route::get('/mapa-procesos/emisiones/pendientes/{id}/revision', [MapaProcesosController::class, 'revisarEmisionPendiente'])
            ->name('mapa-procesos.emisiones.pendientes.revision');

        Route::get('/mapa-procesos/emisiones', [MapaProcesosController::class, 'emisionesAdmin'])
            ->name('mapa-procesos.emisiones.admin');

        Route::post('/mapa-procesos/emisiones/{id}/aprobar', [MapaProcesosController::class, 'aprobarEmision'])
            ->name('mapa-procesos.emisiones.aprobar');

        Route::post('/mapa-procesos/emisiones/{id}/rechazar', [MapaProcesosController::class, 'rechazarEmision'])
            ->name('mapa-procesos.emisiones.rechazar');

        Route::post('/mapa-procesos/emisiones/{id}/devolver', [MapaProcesosController::class, 'devolverEmision'])
            ->name('mapa-procesos.emisiones.devolver');
    });

    // Inactivar / activar documento
    Route::post('/mapa-procesos/{id}/estado', [MapaProcesosController::class, 'cambiarEstado'])
        ->middleware('permisos:'.Permisos::SIG_MAPA_PROCESOS_ELIMINAR)
        ->name('mapa-procesos.documento.estado');
});
