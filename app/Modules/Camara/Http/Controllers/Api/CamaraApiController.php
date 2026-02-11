<?php

namespace App\Modules\Camara\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Camara\Http\Requests\EnrollRequest;
use App\Modules\Camara\Http\Requests\RecognizeLiveRequest;
use App\Modules\Camara\Http\Requests\RecognizeRequest;
use App\Modules\Camara\Services\CameraService;

class CamaraApiController extends Controller
{
  public function __construct(private readonly CameraService $cameraService)
  {
  }

  public function health()
  {
    try {
      $response = $this->cameraService->health();
    } catch (\Throwable $e) {
      return response()->json([
        'status' => 'error',
        'message' => 'No se pudo contactar el servicio.',
      ], 503);
    }

    return response($response->body(), $response->status())
      ->header('Content-Type', $response->header('Content-Type', 'application/json'));
  }

  public function recognize(RecognizeRequest $request)
  {
    $file = $request->file('image');

    try {
      $response = $this->cameraService->recognize($file);
    } catch (\Throwable $e) {
      return response()->json([
        'status' => 'error',
        'message' => 'No se pudo contactar el servicio.',
      ], 503);
    }

    if ($response->failed()) {
      return response()->json([
        'status' => 'error',
        'message' => 'Servicio no disponible.',
      ], $response->status());
    }

    return response($response->body(), $response->status())
      ->header('Content-Type', $response->header('Content-Type', 'application/json'));
  }

  public function enroll(EnrollRequest $request)
  {
    $file = $request->file('image');
    $identificacion = trim((string) $request->input('identificacion'));
    $usrcreacion = $request->user()?->persona?->PerNumDoc;

    try {
      $response = $this->cameraService->enroll($file, $identificacion, $usrcreacion);
    } catch (\Throwable $e) {
      logger()->error('Camara enroll: no se pudo contactar el servicio', [
        'error' => $e->getMessage(),
        'class' => get_class($e),
      ]);

      return response()->json([
        'status' => 'error',
        'message' => 'No se pudo contactar el servicio.',
      ], 503);
    }

    if ($response->failed()) {
      logger()->warning('Camara enroll: API respondio error', [
        'status' => $response->status(),
      ]);

      $contentType = $response->header('Content-Type', '');
      if (str_contains($contentType, 'application/json')) {
        return response()->json(
          $response->json() ?? ['status' => 'error', 'message' => 'Error en API'],
          $response->status()
        );
      }

      return response($response->body(), $response->status())
        ->header('Content-Type', $contentType ?: 'text/plain');
    }

    return response($response->body(), $response->status())
      ->header('Content-Type', $response->header('Content-Type', 'application/json'));
  }

  public function recognizeLive(RecognizeLiveRequest $request)
  {
    $files = $request->file('images', []);
    $evento = (int) $request->input('evento', 2);
    $usrcreacion = $request->user()?->persona?->PerNumDoc;

    logger()->info('Camara recognize-live request', [
      'count' => count($files),
      'evento' => $evento,
      'usrcreacion' => $usrcreacion,
    ]);

    try {
      $response = $this->cameraService->recognizeBatch($files, $evento, $usrcreacion);
    } catch (\Throwable $e) {
      return response()->json([
        'status' => 'error',
        'message' => 'No se pudo contactar el servicio.',
      ], 503);
    }

    logger()->info('Camara recognize-live response', [
      'status' => $response->status(),
    ]);

    if ($response->failed()) {
      return response()->json([
        'status' => 'error',
        'message' => 'Servicio no disponible.',
      ], $response->status());
    }

    return response($response->body(), $response->status())
      ->header('Content-Type', $response->header('Content-Type', 'application/json'));
  }
}
