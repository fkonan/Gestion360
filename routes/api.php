<?php

use App\Modules\Huellero\Http\Controllers\Api\EventoEmpleadoApiController;
use App\Modules\Huellero\Http\Controllers\Api\FingerprintController;
use Illuminate\Support\Facades\Route;

Route::middleware('attendance.api')->group(function () {
    Route::post('/asistencia/eventos/empleados', [EventoEmpleadoApiController::class, 'store'])
        ->name('api.asistencia.eventos.empleados.store');
});

Route::middleware(['web', 'auth'])->group(function () {
    Route::post('/fingerprint/enroll', [FingerprintController::class, 'enroll'])
        ->name('api.fingerprint.enroll');

    Route::post('/fingerprint/verify', [FingerprintController::class, 'verify'])
        ->name('api.fingerprint.verify');

    Route::post('/fingerprint/verify-detailed', [FingerprintController::class, 'verifyDetailed'])
        ->name('api.fingerprint.verifyDetailed');
});

require __DIR__.'/../app/Modules/Sarlaft/Routes/api.php';
