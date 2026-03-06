<?php

use App\Constants\Permisos;
use App\Modules\Huellero\Http\Controllers\DashboardController;
use App\Modules\Huellero\Http\Controllers\FingerprintController;
use App\Modules\Huellero\Http\Controllers\VerificacionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas del Módulo Huellero
|--------------------------------------------------------------------------
|
| Estas rutas manejan la funcionalidad del sistema de huellas dactilares
| incluyendo captura, verificación, eventos y dashboard.
|
*/

// Rutas Fingerprint (migradas desde routes/modulos/huellero.php)
Route::middleware('auth')->group(function () {
    Route::get('/gestion-huellero', [FingerprintController::class, 'gestionHuellero'])
        ->name('fingerprint.gestion');

    Route::get('/fingerprint/enroll', [FingerprintController::class, 'enroll'])
        ->middleware('permisos:' . Permisos::BIOMETRIA_GESTION_HUELLERO_ENROLL)
        ->name('fingerprint.enroll');

    Route::get('/fingerprint/verify', [FingerprintController::class, 'verify'])
        ->middleware('permisos:' . Permisos::BIOMETRIA_GESTION_HUELLERO_VERIFICAR)
        ->name('fingerprint.verify');

    Route::get('/fingerprint/eventos/empleados', [FingerprintController::class, 'eventosEmpleados'])
        ->middleware('permisos:' . Permisos::BIOMETRIA_GESTION_HUELLERO_ACCESO_PERSONAL)
        ->name('fingerprint.eventos.empleados');

    Route::get('/fingerprint/eventos/empleados/manual', [FingerprintController::class, 'eventosEmpleadosManual'])
        ->middleware('permisos:' . Permisos::BIOMETRIA_GESTION_HUELLERO_ACCESO_PERSONAL)
        ->name('fingerprint.eventos.empleados.manual');

    Route::post('/fingerprint/eventos/empleados', [FingerprintController::class, 'storeEventoEmpleado'])
        ->middleware('permisos:' . Permisos::BIOMETRIA_GESTION_HUELLERO_ACCESO_PERSONAL)
        ->name('fingerprint.eventos.empleados.store');

    Route::get('/fingerprint/eventos/empleados/ultimo', [FingerprintController::class, 'ultimoEventoEmpleado'])
        ->middleware('permisos:' . Permisos::BIOMETRIA_GESTION_HUELLERO_ACCESO_PERSONAL)
        ->name('fingerprint.eventos.empleados.ultimo');

    Route::get('/fingerprint/eventos/empleados/ultimos', [FingerprintController::class, 'ultimosEventosEmpleado'])
        ->middleware('permisos:' . Permisos::BIOMETRIA_GESTION_HUELLERO_ACCESO_PERSONAL)
        ->name('fingerprint.eventos.empleados.ultimos');

    Route::get('/fingerprint/eventos/empleados/hoy', [FingerprintController::class, 'eventosHoyEmpleadoPorIdentificacion'])
        ->middleware('permisos:' . Permisos::BIOMETRIA_GESTION_HUELLERO_ACCESO_PERSONAL)
        ->name('fingerprint.eventos.empleados.hoy');

    Route::get('/fingerprint/eventos/conductores', [FingerprintController::class, 'eventosConductores'])
        ->middleware('permisos:' . Permisos::BIOMETRIA_GESTION_HUELLERO_DESCANSO_CONDUCTORES)
        ->name('fingerprint.eventos.conductores');

    Route::post('/fingerprint/eventos/conductores', [FingerprintController::class, 'storeEventoConductor'])
        ->middleware('permisos:' . Permisos::BIOMETRIA_GESTION_HUELLERO_DESCANSO_CONDUCTORES)
        ->name('fingerprint.eventos.conductores.store');

    Route::get('/fingerprint/eventos/conductores/ultimo', [FingerprintController::class, 'ultimoEventoConductor'])
        ->middleware('permisos:' . Permisos::BIOMETRIA_GESTION_HUELLERO_DESCANSO_CONDUCTORES)
        ->name('fingerprint.eventos.conductores.ultimo');

    Route::get('/fingerprint/personas', [FingerprintController::class, 'personas'])
        ->name('fingerprint.personas');
});

// API Routes (sin middleware web para uso con dispositivos externos)
Route::middleware('auth')->prefix('api/huellero')->name('api.huellero.')->group(function () {

    // Verificación automática (para integración con hardware)
    Route::post('/verificar', [VerificacionController::class, 'verificarHuella'])->name('verificar');

    // Eventos de hardware (para notificaciones desde JavaScript)
    Route::post('/evento-hardware', function (\Illuminate\Http\Request $request) {
        return response()->json(['success' => true]);
    })->name('evento_hardware');

    Route::post('/evento-captura', function (\Illuminate\Http\Request $request) {
        return response()->json(['success' => true]);
    })->name('evento_captura');

    // Estado del sistema (para monitoreo)
    Route::get('/estado', [VerificacionController::class, 'estadoSistema'])->name('estado');

    // Dispositivos conectados
    Route::get('/dispositivos', [DashboardController::class, 'dispositivos'])->name('dispositivos');
});




