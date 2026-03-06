<?php

namespace App\Modules\Camara\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Camara\Http\Requests\EnrollRequest;
use App\Modules\Camara\Http\Requests\RecognizeLiveRequest;
use App\Modules\Camara\Http\Requests\RecognizeRequest;
use App\Modules\Camara\Http\Requests\VerifyLiveRequest;
use App\Modules\GestionRRHH\Models\PerPersonas;
use App\Modules\Huellero\Services\RegistrarEventoEmpleadoService;
use Carbon\Carbon;
use App\Modules\Camara\Services\CameraService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CamaraApiController extends Controller
{
  public function __construct(
    private readonly CameraService $cameraService,
    private readonly RegistrarEventoEmpleadoService $registrarEventoEmpleadoService
  ) {
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
    $identCrea = $request->user()?->persona?->PerNumDoc;

    try {
      $response = $this->cameraService->recognize($file, $identCrea);
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
    $files = $request->file('images', []);
    $identificacion = trim((string) $request->input('identificacion'));
    $usrcreacion = $request->user()?->persona?->PerNumDoc;
    $identCrea = $usrcreacion;

    try {
      $response = $this->cameraService->enroll($files, $identificacion, $usrcreacion, $identCrea);
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
    $usrcreacion = $request->user()?->persona?->PerNumDoc;
    $identCrea = $usrcreacion;

    try {
      $response = $this->cameraService->recognizeBatch($files, $usrcreacion, $identCrea);
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

    $payload = $response->json();
    if (!is_array($payload) || !is_array($payload['personas'] ?? null)) {
      return response($response->body(), $response->status())
        ->header('Content-Type', $response->header('Content-Type', 'application/json'));
    }

    $personasProcesadas = [];
    $rechazados = [];
    $procesados = [];

    foreach ($payload['personas'] as $persona) {
      if (!is_array($persona)) {
        continue;
      }

      $identificacion = trim((string) ($persona['identificacion'] ?? ''));
      if ($identificacion === '') {
        continue;
      }

      if (isset($procesados[$identificacion])) {
        continue;
      }
      $procesados[$identificacion] = true;

      $resultado = $this->registrarEventoEmpleadoService->registrar(
        $identificacion,
        null,
        now(),
        $request->user()?->persona?->PerNumDoc,
        $request->user()?->IdUsuario,
        'camara'
      );

      if (($resultado['ok'] ?? false) !== true) {
        $rechazados[] = [
          'identificacion' => $identificacion,
          'motivo' => $resultado['motivo'] ?? 'No se pudo registrar el evento.',
        ];
        continue;
      }

      $fechaRegistro = null;
      if (!empty($resultado['fecha_evento'])) {
        try {
          $fechaRegistro = Carbon::parse($resultado['fecha_evento']);
        } catch (\Throwable $e) {
          $fechaRegistro = null;
        }
      }

      $persona['evento'] = (int) ($resultado['evento'] ?? 0);
      $persona['fecha_registro'] = $fechaRegistro
        ? $fechaRegistro->format('d/m/Y')
        : (string) ($persona['fecha_registro'] ?? '');
      $persona['hora_registro'] = $fechaRegistro
        ? $fechaRegistro->format('g:i a')
        : (string) ($persona['hora_registro'] ?? '');
      $persona['horario_cargo_id'] = $resultado['horario_cargo_id'] ?? null;
      $persona['cargo_id'] = $resultado['cargo_id'] ?? null;
      $persona['flags'] = $resultado['flags'] ?? [
        'llegada_tarde' => false,
        'cargo_especial' => false,
      ];

      $personasProcesadas[] = $persona;
    }

    $payload['personas'] = $personasProcesadas;
    if (!empty($rechazados)) {
      $payload['rechazados'] = $rechazados;
    }

    return response()->json($payload, $response->status());
  }

  public function verifyLive(VerifyLiveRequest $request)
  {
    $files = $request->file('images', []);
    $usrcreacion = $request->user()?->persona?->PerNumDoc;
    $identCrea = $usrcreacion;

    try {
      $response = $this->cameraService->verifyBatch($files, $usrcreacion, $identCrea);
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
    $query = preg_replace('/[^\pL\pN\s]/u', '', $query);
    $query = trim((string) preg_replace('/\s+/', ' ', $query));
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

  public function ultimosEventos()
  {
    $rows = DB::connection('oracle-360')
      ->table('PRS_EVENTOS as e')
      ->leftJoin('PRS_PERSONAS as p', 'p.numero_documento', '=', 'e.identificacion')
      ->whereIn('e.evento', [1, 2])
      ->orderByDesc('e.fecha_creacion')
      ->limit(10)
      ->get([
        'e.identificacion',
        'e.evento',
        'e.descripcion',
        'e.fecha_creacion',
        'p.NOMBRES as nombres',
        'p.PRIMER_APELLIDO as primer_apellido',
        'p.SEGUNDO_APELLIDO as segundo_apellido',
      ]);

    $data = $this->mapEventoRows($rows);

    return response()->json([
      'ok' => true,
      'data' => $data,
    ]);
  }

  public function eventosHoyPorIdentificacion(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'identificacion' => ['required', 'string', 'max:20', 'regex:/^\d+$/'],
    ]);

    if ($validator->fails()) {
      return response()->json([
        'ok' => false,
        'message' => 'Identificacion invalida.',
        'errors' => $validator->errors(),
      ], 422);
    }

    $identificacion = preg_replace('/\D+/', '', (string) $request->input('identificacion'));
    $identificacion = trim((string) $identificacion);

    $rows = DB::connection('oracle-360')
      ->table('PRS_EVENTOS as e')
      ->leftJoin('PRS_PERSONAS as p', 'p.numero_documento', '=', 'e.identificacion')
      ->where('e.identificacion', $identificacion)
      ->whereIn('e.evento', [1, 2])
      ->whereRaw('TRUNC(e.fecha_creacion) = TRUNC(SYSDATE)')
      ->orderByDesc('e.fecha_creacion')
      ->limit(30)
      ->get([
        'e.identificacion',
        'e.evento',
        'e.descripcion',
        'e.fecha_creacion',
        'p.NOMBRES as nombres',
        'p.PRIMER_APELLIDO as primer_apellido',
        'p.SEGUNDO_APELLIDO as segundo_apellido',
      ]);

    return response()->json([
      'ok' => true,
      'identificacion' => $identificacion,
      'data' => $this->mapEventoRows($rows),
    ]);
  }

  private function mapEventoRows($rows)
  {
    return collect($rows)->map(function ($row) {
      $nombre = trim(
        trim((string) ($row->nombres ?? '')) . ' ' .
        trim((string) ($row->primer_apellido ?? '') . ' ' . (string) ($row->segundo_apellido ?? ''))
      );

      $horaEvento = null;
      if (!empty($row->fecha_creacion)) {
        try {
          $horaEvento = Carbon::parse($row->fecha_creacion)->format('g:i a');
        } catch (\Throwable $e) {
          $horaEvento = null;
        }
      }

      return [
        'identificacion' => (string) ($row->identificacion ?? ''),
        'nombre' => $nombre !== '' ? $nombre : 'Sin nombre',
        'descripcion' => (string) ($row->descripcion ?? ''),
        'hora_evento' => $horaEvento,
        'evento' => (int) ($row->evento ?? 0),
      ];
    })->values();
  }
}
