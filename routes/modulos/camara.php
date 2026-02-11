<?php

use App\Modules\Camara\Http\Controllers\CamaraController;
use App\Modules\Camara\Http\Controllers\Api\CamaraApiController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
  Route::get('/reconocimiento-facial', [CamaraController::class, 'index'])
    ->name('reconocimientoFacial.index');

  Route::get('/face-enroll', [CamaraController::class, 'enroll'])
    ->name('face.enroll');

  Route::get('/face-recognize', [CamaraController::class, 'recognize'])
    ->name('face.recognize');

  Route::get('/health', [CamaraApiController::class, 'health'])
    ->name('face-api.health');

  Route::post('/recognize', [CamaraApiController::class, 'recognize'])
    ->name('face-api.recognize');

  Route::post('/enroll', [CamaraApiController::class, 'enroll'])
    ->name('face-api.enroll');

  Route::get('/camera/health', [CamaraApiController::class, 'health'])
    ->name('camera.health');

  Route::post('/camera/enroll', [CamaraApiController::class, 'enroll'])
    ->name('camera.enroll');

  Route::post('/camera/recognize-live', [CamaraApiController::class, 'recognizeLive'])
    ->name('camera.recognize-live');
});
