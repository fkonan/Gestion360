<?php

use App\Modules\Huellero\Http\Controllers\FingerprintController;
use App\Constants\Permisos;
use Illuminate\Support\Facades\Route;

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

    Route::post('/fingerprint/eventos/empleados', [FingerprintController::class, 'storeEventoEmpleado'])
        ->middleware('permisos:' . Permisos::BIOMETRIA_GESTION_HUELLERO_ACCESO_PERSONAL)
        ->name('fingerprint.eventos.empleados.store');

    Route::get('/fingerprint/eventos/empleados/ultimo', [FingerprintController::class, 'ultimoEventoEmpleado'])
        ->middleware('permisos:' . Permisos::BIOMETRIA_GESTION_HUELLERO_ACCESO_PERSONAL)
        ->name('fingerprint.eventos.empleados.ultimo');

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
