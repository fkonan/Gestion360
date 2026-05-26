<?php

use App\Constants\Permisos;
use App\Modules\Configuracion\Http\Controllers\ModuloController;
use App\Modules\GestionRRHH\Http\Controllers\ConductorController;
use App\Modules\GestionRRHH\Http\Controllers\EmpleadoController;
use App\Modules\GestionRRHH\Http\Controllers\Novedades\Descargos\DescargoCitacionController;
use App\Modules\GestionRRHH\Http\Controllers\Novedades\EmpleadoSolicitudController;
use App\Modules\GestionRRHH\Http\Controllers\Novedades\Incapacidades\EmpleadoIncapacidadController;
use App\Modules\GestionRRHH\Http\Controllers\Novedades\PermisosPermanentes\EmpleadoPermisoPermanenteController;
use App\Modules\GestionRRHH\Http\Controllers\Novedades\Vacaciones\EmpleadoVacacionController;
use App\Modules\GestionRRHH\Http\Controllers\PoliticasController;
use Illuminate\Support\Facades\Route;

Route::get('/gestionRRHH/permisos/equipo-jefe/acceso/{token}', [EmpleadoSolicitudController::class, 'accesoDirectoJefe'])
    ->middleware('signed')
    ->name('gestionRRHH.permisos.jefe.magic');

Route::get('/gestionRRHH/gestion-empleado/permisos/equipo-jefe/acceso/{token}', [EmpleadoSolicitudController::class, 'accesoDirectoJefe'])
    ->middleware('signed');

Route::get('/administracion/empleados/permisos/equipo-jefe/acceso/{token}', [EmpleadoSolicitudController::class, 'accesoDirectoJefe'])
    ->middleware('signed')
    ->name('empleados.permisos.jefe.magic');

// Rutas Modulo Gestion RRHH
Route::prefix('gestionRRHH')->middleware(['auth', 'permisos:'.Permisos::GESTION_RRHH_ACCEDER, 'modulo.activo:2', 'rrhh.consumo'])->group(function () {
    Route::prefix('gestion-empleado')->middleware(['permisos:'.Permisos::GESTION_RRHH_GESTION_EMPLEADO_ACCEDER, 'submodulo.activo:7'])->group(function () {
        Route::get('/', [ModuloController::class, 'getGestionEmpleado'])->name('gestion-incapacidades.index');

        // Registrar descanso conductores
        Route::get('/formDescansoConductores', [ConductorController::class, 'formDescansoConductores'])->name('conductor.descanso');
        Route::post('/descansoConductores', [ConductorController::class, 'registrarEvento'])->name('registrar.evento');

        // Consulta firmas politicas conductores
        Route::get('/firma-politicas', [PoliticasController::class, 'index'])->name('politicas.index');
        Route::post('/firma-politicas/conductor', [PoliticasController::class, 'politicasFirmadas'])->name('politicas.conductor');
        Route::get('/firmas/descargar', [PoliticasController::class, 'generarPDFPolitica'])->name('firmas.descargar');

        // Solicitar nuevo ingreso empleado
        Route::get('/solicitud-nuevo-ingreso', [EmpleadoController::class, 'nuevoIngreso'])->name('empleado.nuevoIngreso');
        Route::post('/gestion-nuevo-ingreso', [EmpleadoController::class, 'gestionNuevoIngreso'])->name('gestion.nuevoIngreso');

        // Novedad revision preoperacional
        Route::get('/preoperacional', [ConductorController::class, 'revisionPreoperacional'])->name('conductor.preoperacional.index');
        Route::post('/preoperacional-novedad', [ConductorController::class, 'novedadPreoperacional'])->name('conductor.preoperacional.novedad');

        // Buscar persona por identificacion (AJAX)
        Route::get('/persona/buscar/{identificacion}', [EmpleadoController::class, 'buscar'])
            ->name('persona.buscar');

        // Obtener ultimo evento descanso conductor (AJAX)
        Route::post('/obtener-ultimo-evento', [ConductorController::class, 'obtenerUltimoEventoDescanso'])
            ->name('obtener.ultimo.evento');
    });
});

Route::prefix('gestionRRHH')->middleware(['auth', 'modulo.activo:2', 'rrhh.consumo'])->group(function () {
    Route::get('/solicitudes', [EmpleadoSolicitudController::class, 'index'])
        ->middleware(['submodulo.activo:7'])
        ->name('gestionRRHH.solicitudes.index');
    Route::get('/solicitudes/radicar', [EmpleadoSolicitudController::class, 'radicar'])
        ->middleware(['submodulo.activo:7'])
        ->name('gestionRRHH.solicitudes.radicar');
    Route::get('/solicitudes/radicar/permiso', [EmpleadoSolicitudController::class, 'create'])
        ->middleware(['submodulo.activo:7'])
        ->name('gestionRRHH.permisos.create');
    Route::get('/solicitudes/radicar/permiso-permanente', [EmpleadoPermisoPermanenteController::class, 'create'])
        ->middleware(['submodulo.activo:7'])
        ->name('gestionRRHH.permisos.permisos-permanentes.create');
    Route::get('/solicitudes/radicar/vacaciones', [EmpleadoVacacionController::class, 'create'])
        ->middleware(['submodulo.activo:7'])
        ->name('gestionRRHH.permisos.vacaciones.create');
    Route::get('/solicitudes/radicar/incapacidad', [EmpleadoIncapacidadController::class, 'create'])
        ->middleware(['submodulo.activo:7'])
        ->name('gestionRRHH.permisos.incapacidades.create');
    Route::get('/solicitudes/descargos/citar', [DescargoCitacionController::class, 'create'])
        ->middleware(['submodulo.activo:7', 'permisos:'.Permisos::GESTION_RRHH_PERMISOS_PERMISOS_RRHH])
        ->name('gestionRRHH.descargos.citaciones.create');
    Route::post('/solicitudes/descargos/citar', [DescargoCitacionController::class, 'store'])
        ->middleware(['submodulo.activo:7', 'permisos:'.Permisos::GESTION_RRHH_PERMISOS_PERMISOS_RRHH])
        ->name('gestionRRHH.descargos.citaciones.store');

    Route::prefix('permisos')
        ->name('gestionRRHH.permisos.')
        ->middleware(['submodulo.activo:7'])
        ->group(function () {
            Route::get('/', [EmpleadoSolicitudController::class, 'index'])->name('index');
            Route::post('/permisos-permanentes', [EmpleadoPermisoPermanenteController::class, 'store'])->name('permisos-permanentes.store');
            Route::post('/vacaciones', [EmpleadoVacacionController::class, 'store'])->name('vacaciones.store');
            Route::post('/incapacidades', [EmpleadoIncapacidadController::class, 'store'])->name('incapacidades.store');
            Route::get('/incapacidades/diagnosticos', [EmpleadoIncapacidadController::class, 'diagnosticos'])->name('incapacidades.diagnosticos');
            Route::get('/incapacidades/persona/{documento}', [EmpleadoIncapacidadController::class, 'persona'])->name('incapacidades.persona');
            Route::get('/incapacidades/{idNovedad}/gestion', [EmpleadoIncapacidadController::class, 'gestion'])
                ->middleware('permisos:'.Permisos::GESTION_RRHH_PERMISOS_PERMISOS_RRHH)
                ->name('incapacidades.gestion');
            Route::put('/incapacidades/{idNovedad}', [EmpleadoIncapacidadController::class, 'actualizarGestion'])
                ->middleware('permisos:'.Permisos::GESTION_RRHH_PERMISOS_PERMISOS_RRHH)
                ->name('incapacidades.update');
            Route::post('/incapacidades/{idNovedad}/aprobar', [EmpleadoIncapacidadController::class, 'aprobar'])
                ->middleware('permisos:'.Permisos::GESTION_RRHH_PERMISOS_PERMISOS_RRHH)
                ->name('incapacidades.aprobar');
            Route::post('/incapacidades/{idNovedad}/rechazar', [EmpleadoIncapacidadController::class, 'rechazar'])
                ->middleware('permisos:'.Permisos::GESTION_RRHH_PERMISOS_PERMISOS_RRHH)
                ->name('incapacidades.rechazar');
            Route::post('/incapacidades/{idNovedad}/seguimiento', [EmpleadoIncapacidadController::class, 'registrarSeguimiento'])
                ->middleware('permisos:'.Permisos::GESTION_RRHH_PERMISOS_PERMISOS_RRHH)
                ->name('incapacidades.seguimiento.store');
            Route::get('/novedades', [EmpleadoSolicitudController::class, 'novedades'])
                ->middleware('permisos:'.Permisos::GESTION_RRHH_PERMISOS_NOVEDADES)
                ->name('novedades');
            Route::get('/mis-novedades', [EmpleadoSolicitudController::class, 'misNovedades'])->name('mis-novedades');
            Route::put('/novedades/{id}/horas', [EmpleadoSolicitudController::class, 'actualizarHoras'])
                ->middleware('permisos:'.Permisos::GESTION_RRHH_PERMISOS_NOVEDADES)
                ->name('novedades.horas.update');
            Route::get('/mis-solicitudes', [EmpleadoSolicitudController::class, 'misSolicitudes'])->name('mis-solicitudes');
            Route::get('/equipo-jefe', [EmpleadoSolicitudController::class, 'solicitudesJefe'])->name('jefe');
            Route::get('/pendientes-rrhh', [EmpleadoSolicitudController::class, 'solicitudesPendientesRrhh'])
                ->middleware('permisos:'.Permisos::GESTION_RRHH_PERMISOS_PERMISOS_RRHH)
                ->name('rrhh');
            Route::post('/', [EmpleadoSolicitudController::class, 'store'])->name('store');
            Route::post('/{idNovedad}/aprobar-jefe', [EmpleadoSolicitudController::class, 'aprobarJefe'])->name('aprobar-jefe');
            Route::post('/{idNovedad}/aprobar-rrhh', [EmpleadoSolicitudController::class, 'aprobarRrhh'])->name('aprobar-rrhh');
            Route::post('/{idNovedad}/rechazar', [EmpleadoSolicitudController::class, 'rechazar'])->name('rechazar');
            Route::post('/{idNovedad}/anular', [EmpleadoSolicitudController::class, 'anular'])->name('anular');
            Route::get('/{idNovedad}/seguimiento', [EmpleadoSolicitudController::class, 'seguimiento'])->name('seguimiento');
            Route::get('/{idNovedad}/pdf', [EmpleadoSolicitudController::class, 'pdf'])->name('pdf');
            Route::get('/{idNovedad}/documentos', [EmpleadoSolicitudController::class, 'documentos'])->name('documentos');
            Route::get('/documentos/{idDocumento}/ver', [EmpleadoSolicitudController::class, 'verDocumento'])->name('documentos.ver');
            Route::get('/persona/{documento}', [EmpleadoSolicitudController::class, 'persona'])->name('persona');
        });
});
