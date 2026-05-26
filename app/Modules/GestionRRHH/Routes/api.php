<?php

use App\Http\Controllers\Api\JwtAuthController;
use App\Modules\GestionRRHH\Http\Controllers\Novedades\Incapacidades\Api\EmpleadoIncapacidadApiController;
use App\Modules\GestionRRHH\Http\Controllers\Novedades\Api\EmpleadoSolicitudApiController as EmpleadoPermisoApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('v2')->middleware('rrhh.consumo')->group(function () {
    Route::post('/auth/token', [JwtAuthController::class, 'token'])
        ->middleware('throttle:20,1')
        ->name('api.v2.auth.token');

    Route::middleware(['jwt.api:empleados.permisos', 'throttle:30,1'])->group(function () {
        Route::get('/empleados/permisos', [EmpleadoPermisoApiController::class, 'index'])
            ->name('api.v2.empleados.permisos.index');
        Route::get('/empleados/novedades', [EmpleadoPermisoApiController::class, 'novedades'])
            ->name('api.v2.empleados.novedades.index');
        Route::post('/empleados/permisos', [EmpleadoPermisoApiController::class, 'store'])
            ->name('api.v2.empleados.permisos.store');
        Route::post('/empleados/permisos/radicar', [EmpleadoPermisoApiController::class, 'radicar'])
            ->name('api.v2.empleados.permisos.radicar');
        Route::post('/empleados/vacaciones', [EmpleadoPermisoApiController::class, 'storeVacaciones'])
            ->name('api.v2.empleados.vacaciones.store');
        Route::post('/empleados/vacaciones/radicar', [EmpleadoPermisoApiController::class, 'radicarVacaciones'])
            ->name('api.v2.empleados.vacaciones.radicar');
        Route::post('/empleados/permisos-permanentes', [EmpleadoPermisoApiController::class, 'storePermisoPermanente'])
            ->name('api.v2.empleados.permisos-permanentes.store');
        Route::post('/empleados/permisos-permanentes/radicar', [EmpleadoPermisoApiController::class, 'radicarPermisoPermanente'])
            ->name('api.v2.empleados.permisos-permanentes.radicar');
        Route::get('/empleados/permisos-permanentes/radicar/schema', [EmpleadoPermisoApiController::class, 'schemaRadicarPermisoPermanente'])
            ->name('api.v2.empleados.permisos-permanentes.radicar.schema');
        Route::post('/empleados/solicitudes/{idNovedad}/anular', [EmpleadoPermisoApiController::class, 'anularSolicitud'])
            ->name('api.v2.empleados.solicitudes.anular');
        Route::get('/empleados/permisos/radicar/schema', [EmpleadoPermisoApiController::class, 'schemaRadicar'])
            ->name('api.v2.empleados.permisos.radicar.schema');
        Route::get('/empleados/vacaciones/radicar/schema', [EmpleadoPermisoApiController::class, 'schemaRadicarVacaciones'])
            ->name('api.v2.empleados.vacaciones.radicar.schema');
        Route::get('/empleados/permisos/aprobaciones/schema', [EmpleadoPermisoApiController::class, 'schemaAprobaciones'])
            ->name('api.v2.empleados.permisos.aprobaciones.schema');
    });

    Route::middleware(['jwt.api:empleados.permisos.jefe', 'throttle:30,1'])->group(function () {
        Route::get('/empleados/solicitudes/equipo-jefe', [EmpleadoPermisoApiController::class, 'equipoJefe'])
            ->name('api.v2.empleados.solicitudes.jefe.index');
        Route::post('/empleados/solicitudes/{idNovedad}/gestionar-jefe', [EmpleadoPermisoApiController::class, 'gestionarJefe'])
            ->name('api.v2.empleados.solicitudes.gestionar-jefe');
    });

    Route::middleware(['jwt.api:empleados.permisos.rrhh', 'throttle:30,1'])->group(function () {
        Route::get('/empleados/solicitudes/pendientes-rrhh', [EmpleadoPermisoApiController::class, 'pendientesRrhh'])
            ->name('api.v2.empleados.solicitudes.rrhh.index');
        Route::post('/empleados/solicitudes/{idNovedad}/gestionar-rrhh', [EmpleadoPermisoApiController::class, 'gestionarRrhh'])
            ->name('api.v2.empleados.solicitudes.gestionar-rrhh');
    });

    Route::middleware(['jwt.api:empleados.incapacidades', 'throttle:30,1'])->group(function () {
        Route::get('/empleados/incapacidades', [EmpleadoIncapacidadApiController::class, 'index'])
            ->name('api.v2.empleados.incapacidades.index');
        Route::post('/empleados/incapacidades', [EmpleadoIncapacidadApiController::class, 'store'])
            ->name('api.v2.empleados.incapacidades.store');
        Route::post('/empleados/incapacidades/radicar', [EmpleadoIncapacidadApiController::class, 'radicar'])
            ->name('api.v2.empleados.incapacidades.radicar');
        Route::get('/empleados/incapacidades/radicar/schema', [EmpleadoIncapacidadApiController::class, 'schemaRadicar'])
            ->name('api.v2.empleados.incapacidades.radicar.schema');
        Route::get('/empleados/incapacidades/catalogos', [EmpleadoIncapacidadApiController::class, 'catalogos'])
            ->name('api.v2.empleados.incapacidades.catalogos');
    });
});
