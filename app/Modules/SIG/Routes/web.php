<?php

use App\Constants\Permisos;
use App\Modules\SIG\Http\Controllers\MapaProcesosController;
use Illuminate\Support\Facades\Route;

// Rutas SIG
Route::prefix('sig')->middleware(['auth', 'permisos:'.Permisos::SIG_ACCEDER, 'modulo.activo:18'])->group(function () {

    Route::get('/', [MapaProcesosController::class, 'index'])->name('mapaProcesos.index');

    // Entrada principal del mapa
    Route::get('/mapa-procesos', [MapaProcesosController::class, 'index'])->name('mapa-procesos.index');
    Route::get('/mapa-procesos/listado-maestro', [MapaProcesosController::class, 'descargarListadoMaestro'])
        ->middleware('permisos:'.Permisos::SIG_MAPA_PROCESOS_LISTADO_MAESTRO)
        ->name('mapa-procesos.listado-maestro');

    Route::get('/mapa-procesos/documento/{id}/emisiones', [MapaProcesosController::class, 'emisiones'])
        ->middleware('permisos:'.Permisos::SIG_MAPA_PROCESOS_VER_EMISION)
        ->name('mapa-procesos.documento.emisiones');

    Route::post('/mapa-procesos/documento/paginas/calcular', [MapaProcesosController::class, 'calcularPaginasArchivo'])
        ->name('mapa-procesos.documento.paginas.calcular');

    // Submodulo: Mis solicitudes
    Route::get('/mis-solicitudes', [MapaProcesosController::class, 'emisionesDevueltasUsuario'])
        ->name('mis-solicitudes.index');

    // Compatibilidad con rutas anteriores
    Route::get('/solicitudes', function () {
        return redirect()->route('mis-solicitudes.index');
    });

    Route::get('/mapa-procesos/emisiones/devueltas', function () {
        return redirect()->route('mis-solicitudes.index');
    })
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

    // Submodulo: Gestion de solicitudes
    Route::middleware('permisos:'.Permisos::SIG_MAPA_PROCESOS_ACCEDER)->group(function () {
        Route::get('/gestion-solicitudes', [MapaProcesosController::class, 'emisionesPendientes'])
            ->name('gestion-solicitudes.index');

        Route::get('/gestion-solicitudes/{id}/revision', [MapaProcesosController::class, 'revisarEmisionPendiente'])
            ->name('gestion-solicitudes.revision');

        Route::post('/gestion-solicitudes/{id}/aprobar', [MapaProcesosController::class, 'aprobarEmision'])
            ->name('gestion-solicitudes.aprobar');

        Route::post('/gestion-solicitudes/{id}/rechazar', [MapaProcesosController::class, 'rechazarEmision'])
            ->name('gestion-solicitudes.rechazar');

        Route::post('/gestion-solicitudes/{id}/devolver', [MapaProcesosController::class, 'devolverEmision'])
            ->name('gestion-solicitudes.devolver');

        // Compatibilidad con rutas anteriores
        Route::get('/mapa-procesos/emisiones/pendientes', function () {
            return redirect()->route('gestion-solicitudes.index');
        })->name('mapa-procesos.emisiones.pendientes');

        Route::get('/mapa-procesos/emisiones/pendientes/{id}/revision', function (int $id) {
            return redirect()->route('gestion-solicitudes.revision', ['id' => $id]);
        })->name('mapa-procesos.emisiones.pendientes.revision');

        Route::get('/mapa-procesos/emisiones', function () {
            return redirect()->route('gestion-solicitudes.index');
        })->name('mapa-procesos.emisiones.admin');

        Route::post('/mapa-procesos/emisiones/{id}/aprobar', [MapaProcesosController::class, 'aprobarEmision'])
            ->name('mapa-procesos.emisiones.aprobar');

        Route::post('/mapa-procesos/emisiones/{id}/rechazar', [MapaProcesosController::class, 'rechazarEmision'])
            ->name('mapa-procesos.emisiones.rechazar');

        Route::post('/mapa-procesos/emisiones/{id}/devolver', [MapaProcesosController::class, 'devolverEmision'])
            ->name('mapa-procesos.emisiones.devolver');
    });

    // Vista principal de mapa (solo consulta)
    Route::get('/mapa-procesos/{categoria}', [MapaProcesosController::class, 'porCategoria'])
        ->name('mapa-procesos.categoria');

    // Inactivar / activar documento
    Route::post('/mapa-procesos/{id}/estado', [MapaProcesosController::class, 'cambiarEstado'])
        ->middleware('permisos:'.Permisos::SIG_MAPA_PROCESOS_ELIMINAR)
        ->name('mapa-procesos.documento.estado');
});
