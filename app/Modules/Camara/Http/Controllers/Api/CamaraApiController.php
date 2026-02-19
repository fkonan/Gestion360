<?php

namespace App\Modules\Camara\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Camara\Http\Requests\EnrollRequest;
use App\Modules\Camara\Http\Requests\RecognizeLiveRequest;
use App\Modules\Camara\Http\Requests\RecognizeRequest;
use App\Modules\GestionRRHH\Models\PerPersonas;
use App\Modules\Camara\Services\CameraService;
use Illuminate\Http\Request;

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

  public function sessionKeepalive(Request $request)
  {
    // Fuerza escritura de actividad para extender la sesion en vistas de camara.
    $request->session()->put('camara_last_keepalive_at', now()->timestamp);

    return response()->json([
      'ok' => true,
      'timestamp' => now()->toIso8601String(),
    ]);
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

    try {
      $response = $this->cameraService->recognizeBatch($files, $evento, $usrcreacion);
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

  public function personas(Request $request)
  {
    $query = trim((string) $request->query('query', ''));
    if ($query === '') {
      return response()->json([]);
    }

    $personasQuery = PerPersonas::query()
      ->where('tipdocumento', 1)
      ->where('estado', 'ACTIVO')
      ->where('estborrado', 0);

    // Para busqueda por documento, usar prefijo permite aprovechar mejor indices.
    if (preg_match('/^\d+$/', $query)) {
      $personasQuery->where('identificacion', 'like', $query . '%');
    } else {
      $personasQuery->where(function ($q) use ($query) {
        $q->where('pnombre', 'like', '%' . $query . '%')
          ->orWhere('snombre', 'like', '%' . $query . '%')
          ->orWhere('papellido', 'like', '%' . $query . '%')
          ->orWhere('sapellido', 'like', '%' . $query . '%');
      });
    }

    $personas = $personasQuery
      ->orderBy('identificacion')
      ->limit(20)
      ->get(['id', 'identificacion', 'pnombre', 'snombre', 'papellido', 'sapellido']);

    $data = $personas->map(function ($persona) {
      $nombre = trim(
        trim((string) $persona->pnombre . ' ' . (string) $persona->snombre) . ' ' .
          trim((string) $persona->papellido . ' ' . (string) $persona->sapellido)
      );
      $documento = (string) $persona->identificacion;

      return [
        'id' => $persona->id,
        'text' => $documento . ' - ' . $nombre,
        'documento' => $documento,
        'nombre' => $nombre,
      ];
    });

    return response()->json($data);
  }
}
