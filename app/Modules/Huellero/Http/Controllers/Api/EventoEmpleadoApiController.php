<?php

namespace App\Modules\Huellero\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Huellero\Services\RegistrarEventoEmpleadoService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EventoEmpleadoApiController extends Controller
{
  public function __construct(
    private readonly RegistrarEventoEmpleadoService $registrarEventoEmpleadoService
  ) {
  }

  public function store(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'identificacion' => ['nullable', 'string', 'max:50', 'required_without:identificaciones'],
      'identificaciones' => ['nullable', 'array', 'min:1', 'required_without:identificacion'],
      'identificaciones.*' => ['required', 'string', 'max:50'],
      'evento' => ['nullable', 'in:1,2'],
      'fecha' => ['nullable', 'date'],
      'documento_usuario' => ['nullable', 'string', 'max:50'],
      'usuario_notificacion' => ['nullable', 'integer'],
      'origen' => ['nullable', 'in:api,camara,huella'],
    ]);

    if ($validator->fails()) {
      return response()->json([
        'ok' => false,
        'error' => $validator->errors()->first(),
      ], 422);
    }

    $payload = $validator->validated();
    $eventoManual = array_key_exists('evento', $payload) && $payload['evento'] !== null
      ? (int) $payload['evento']
      : null;
    $fecha = isset($payload['fecha']) ? Carbon::parse($payload['fecha']) : null;
    $identificaciones = $this->normalizarIdentificaciones($payload);
    $origen = isset($payload['origen']) ? (string) $payload['origen'] : 'api';
    $documentoUsuario = isset($payload['documento_usuario']) ? (string) $payload['documento_usuario'] : null;
    $usuarioNotificacion = isset($payload['usuario_notificacion']) ? (int) $payload['usuario_notificacion'] : null;

    if (count($identificaciones) === 1 && !array_key_exists('identificaciones', $payload)) {
      $resultado = $this->registrarEventoEmpleadoService->registrar(
        $identificaciones[0],
        $eventoManual,
        $fecha,
        $documentoUsuario,
        $usuarioNotificacion,
        $origen
      );

      if (($resultado['ok'] ?? false) !== true) {
        return response()->json([
          'ok' => false,
          'error' => $resultado['error'] ?? 'No se pudo guardar el evento.',
          'status' => $resultado['status'] ?? null,
          'evento' => $resultado['evento'] ?? null,
          'motivo' => $resultado['motivo'] ?? null,
          'horario_cargo_id' => $resultado['horario_cargo_id'] ?? null,
          'cargo_id' => $resultado['cargo_id'] ?? null,
          'flags' => $resultado['flags'] ?? [
            'llegada_tarde' => false,
            'cargo_especial' => false,
          ],
          'origen' => $resultado['origen'] ?? 'api',
          'data' => $resultado['data'] ?? null,
        ], (int) ($resultado['http_status'] ?? 422));
      }

      return response()->json([
        'ok' => true,
        'status' => $resultado['status'] ?? 'OK',
        'evento' => $resultado['evento'] ?? null,
        'motivo' => null,
        'horario_cargo_id' => $resultado['horario_cargo_id'] ?? null,
        'cargo_id' => $resultado['cargo_id'] ?? null,
        'flags' => $resultado['flags'] ?? [
          'llegada_tarde' => false,
          'cargo_especial' => false,
        ],
        'fecha_evento' => $resultado['fecha_evento'] ?? null,
        'data' => $resultado['data'] ?? null,
        'origen' => $resultado['origen'] ?? 'api',
      ]);
    }

    $total = count($identificaciones);
    $exitosos = 0;
    $fallidos = 0;

    foreach ($identificaciones as $identificacion) {
      $resultado = $this->registrarEventoEmpleadoService->registrar(
        $identificacion,
        $eventoManual,
        $fecha,
        $documentoUsuario,
        $usuarioNotificacion,
        $origen
      );

      if (($resultado['ok'] ?? false) === true) {
        $exitosos++;
        continue;
      }

      $fallidos++;
    }

    return response()->json([
      'ok' => $fallidos === 0,
      'status' => $fallidos === 0 ? 'OK' : ($exitosos > 0 ? 'PARCIAL' : 'RECHAZADO'),
      'resumen' => [
        'total' => $total,
        'exitosos' => $exitosos,
        'fallidos' => $fallidos,
      ],
      'origen' => $origen,
    ]);
  }

  private function normalizarIdentificaciones(array $payload): array
  {
    $identificaciones = [];

    if (isset($payload['identificacion'])) {
      $identificaciones[] = (string) $payload['identificacion'];
    }

    foreach (($payload['identificaciones'] ?? []) as $identificacion) {
      $identificaciones[] = (string) $identificacion;
    }

    return collect($identificaciones)
      ->map(function (string $identificacion) {
        return trim($identificacion);
      })
      ->filter(function (string $identificacion) {
        return $identificacion !== '';
      })
      ->unique()
      ->values()
      ->all();
  }
}
