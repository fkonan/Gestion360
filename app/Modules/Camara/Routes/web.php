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

    Route::get('/face-recognize-ip', [CamaraController::class, 'recognizeIp'])
        ->middleware('permisos:' . Permisos::BIOMETRIA_GESTION_CAMARA_RECONOCER)
        ->name('face.recognize-ip');

    Route::get('/face-verify', [CamaraController::class, 'verify'])
        ->middleware('permisos:' . Permisos::BIOMETRIA_GESTION_CAMARA_RECONOCER)
        ->name('face.verify');

    Route::get('/camera/ip-preview', [CamaraController::class, 'ipPreview'])
        ->middleware('permisos:' . Permisos::BIOMETRIA_GESTION_CAMARA_RECONOCER)
        ->name('camera.ip.preview');

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

    Route::get('/camera/ultimos-eventos', [CamaraApiController::class, 'ultimosEventos'])
        ->middleware('permisos:' . Permisos::BIOMETRIA_GESTION_CAMARA_RECONOCER)
        ->name('camera.ultimos-eventos');

    Route::get('/camera/eventos-hoy', [CamaraApiController::class, 'eventosHoyPorIdentificacion'])
        ->middleware('permisos:' . Permisos::BIOMETRIA_GESTION_CAMARA_RECONOCER)
        ->name('camera.eventos-hoy');

    Route::post('/camera/verify-live', [CamaraApiController::class, 'verifyLive'])
        ->middleware('permisos:' . Permisos::BIOMETRIA_GESTION_CAMARA_RECONOCER)
        ->name('camera.verify-live');

    Route::get('/camera/ip/snapshot', [CamaraApiController::class, 'ipSnapshot'])
        ->middleware('permisos:' . Permisos::BIOMETRIA_GESTION_CAMARA_RECONOCER)
        ->name('camera.ip.snapshot');

    Route::get('/camera/ip/mjpeg', [CamaraApiController::class, 'ipMjpegStream'])
        ->middleware('permisos:' . Permisos::BIOMETRIA_GESTION_CAMARA_RECONOCER)
        ->name('camera.ip.mjpeg');

    Route::get('/camera/ip/diagnostic', [CamaraApiController::class, 'ipDiagnostic'])
        ->middleware('permisos:' . Permisos::BIOMETRIA_GESTION_CAMARA_RECONOCER)
        ->name('camera.ip.diagnostic');
});
