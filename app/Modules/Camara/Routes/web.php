<?php

use App\Constants\Permisos;
use App\Modules\Camara\Http\Controllers\Api\CamaraApiController;
use App\Modules\Camara\Http\Controllers\CamaraController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'deny.mobile'])->group(function () {
    Route::get('/reconocimiento-facial', [CamaraController::class, 'index'])
        ->name('reconocimientoFacial.index');

    Route::get('/face-enroll', [CamaraController::class, 'enroll'])
        ->middleware('permisos:' . Permisos::BIOMETRIA_GESTION_CAMARA_ENROLL)
        ->name('face.enroll');

    Route::get('/face-recognize', [CamaraController::class, 'recognize'])
        ->middleware('permisos:' . Permisos::BIOMETRIA_GESTION_CAMARA_RECONOCER)
        ->name('face.recognize');

    Route::get('/health', [CamaraApiController::class, 'health'])
        ->name('face-api.health');

    Route::post('/recognize', [CamaraApiController::class, 'recognize'])
        ->name('face-api.recognize');

    Route::post('/enroll', [CamaraApiController::class, 'enroll'])
        ->name('face-api.enroll');

    Route::get('/camera/health', [CamaraApiController::class, 'health'])
        ->name('camera.health');

    Route::get('/camera/session-keepalive', [CamaraApiController::class, 'sessionKeepalive'])
        ->middleware('permisos:' . Permisos::BIOMETRIA_GESTION_CAMARA_RECONOCER)
        ->name('camera.session-keepalive');

    Route::get('/camera/personas', [CamaraApiController::class, 'personas'])
        ->middleware('permisos:' . Permisos::BIOMETRIA_GESTION_CAMARA_ENROLL)
        ->name('camera.personas');

    Route::post('/camera/enroll', [CamaraApiController::class, 'enroll'])
        ->middleware('permisos:' . Permisos::BIOMETRIA_GESTION_CAMARA_ENROLL)
        ->name('camera.enroll');

    Route::post('/camera/recognize-live', [CamaraApiController::class, 'recognizeLive'])
        ->middleware('permisos:' . Permisos::BIOMETRIA_GESTION_CAMARA_RECONOCER)
        ->name('camera.recognize-live');
});
