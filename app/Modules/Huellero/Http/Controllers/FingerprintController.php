<?php

namespace App\Modules\Huellero\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\GestionRRHH\Models\PerContratoPersona;
use App\Modules\GestionRRHH\Models\PerPersonas;
use App\Modules\Huellero\Models\PerIdentHuella;
use App\Modules\Huellero\Models\PrsHuellaEventos;
use App\Modules\Huellero\Models\PrsPersonas;
use App\Modules\Huellero\Services\RegistrarEventoEmpleadoService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Throwable;

class FingerprintController extends Controller
{
  public function __construct(
    private readonly RegistrarEventoEmpleadoService $registrarEventoEmpleadoService
  ) {
  }

  public function enroll(Request $request)
  {
    $idCreacion = null;
    $documento = $request->user()?->persona?->PerNumDoc;
    if ($documento) {
      $idCreacion = PrsPersonas::query()
        ->where('numero_documento', $documento)
        ->value('id');
    }

    return view('huellero::fingerprint.enroll', [
      'dedos' => PerIdentHuella::dedosDisponibles(),
      'idCreacion' => $idCreacion,
    ]);
  }

  public function gestionHuellero()
  {
    return view('huellero::modulos.gestionHuellero');
  }

  public function verify(Request $request)
  {
    return view('huellero::fingerprint.verify', [
      'dedos' => PerIdentHuella::dedosDisponibles(),
    ]);
  }

  public function eventosEmpleados(Request $request)
  {
    return $this->vistaEventosEmpleados(true);
  }

  public function eventosEmpleadosManual(Request $request)
  {
    return $this->vistaEventosEmpleados(false);
  }

  public function eventosConductores(Request $request)
  {
    return view('huellero::fingerprint.descanso_conductores');
  }

  private function vistaEventosEmpleados(bool $automatico)
  {
    return view('huellero::fingerprint.ingreso_personal', [
      'modoAutomatico' => $automatico,
      'tituloIngresoPersonal' => $automatico
        ? 'Ingreso personal automatico'
        : 'Ingreso personal manual',
    ]);
  }

  public function storeEventoEmpleado(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'evento' => ['nullable', 'in:1,2'],
      'descripcion' => ['nullable', 'in:entrada,salida'],
      'identificacion' => ['required', 'string', 'max:50'],
      'fecha' => ['nullable', 'date'],
    ]);

    if ($validator->fails()) {
      $this->huelleroLogger()->warning('Huellero evento empleado validation failed', [
        'identificacion' => $request->input('identificacion'),
        'error' => $validator->errors()->first(),
      ]);
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

    $resultado = $this->registrarEventoEmpleadoService->registrar(
      (string) $payload['identificacion'],
      $eventoManual,
      $fecha,
      $request->user()?->persona?->PerNumDoc,
      $request->user()?->IdUsuario,
      'huella'
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
      'data' => $resultado['data'] ?? null,
    ]);
  }

  private function huelleroLogger()
  {
    return Log::build([
      'driver' => 'daily',
      'path' => storage_path('logs/huellero/huellero.log'),
      'days' => 7,
    ]);
  }

  public function personas(Request $request)
  {
    $query = trim((string) $request->query('query', ''));
    if ($query === '') {
      return response()->json([]);
    }

    $personas = PerPersonas::query()
      ->where('identificacion', 'like', $query . '%')
      ->where('tipdocumento', 1)
      ->where('estado', 'ACTIVO')
      ->where('estborrado', 0)
      ->orderBy('identificacion')
      ->limit(20)
      ->get(['id', 'identificacion', 'pnombre', 'snombre', 'papellido', 'sapellido']);

    $eventoPorPersona = [];
    $personaIds = $personas->pluck('id')->filter()->values();
    if ($personaIds->isNotEmpty()) {
      $connection = (new PerPersonas())->getConnectionName() ?: config('database.default');
      $roles = DB::connection($connection)
        ->table('per_empresapersonas')
        ->select('pe_id_pe', 'tp_id')
        ->whereIn('pe_id_pe', $personaIds->all())
        ->where('activo', 1)
        ->where('estborrado', 0)
        ->whereNull('fecfin')
        ->whereIn('tp_id', [1, 11])
        ->get();

      foreach ($roles as $rol) {
        $personaId = $rol->pe_id_pe;
        $tpId = (int) $rol->tp_id;
        if ($tpId === 1) {
          $eventoPorPersona[$personaId] = 1;
          continue;
        }
        if ($tpId === 11 && !isset($eventoPorPersona[$personaId])) {
          $eventoPorPersona[$personaId] = 2;
        }
      }
    }

    $data = $personas->map(function ($persona) use ($eventoPorPersona) {
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
        'evento' => $eventoPorPersona[$persona->id] ?? 1,
        'cargo' => null,
      ];
    });

    return response()->json($data);
  }

  public function storeEventoConductor(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'evento' => ['required', 'in:3,4'],
      'descripcion' => ['required', 'in:salida,regreso,salida a descanso,regreso de descanso'],
      'identificacion' => ['required', 'string', 'max:50'],
      'fecha' => ['nullable', 'date'],
    ]);

    if ($validator->fails()) {
      $this->huelleroLogger()->warning('Huellero evento conductor validation failed', [
        'identificacion' => $request->input('identificacion'),
        'error' => $validator->errors()->first(),
      ]);
      return response()->json([
        'ok' => false,
        'error' => $validator->errors()->first(),
      ], 422);
    }

    $payload = $validator->validated();
      $nombre = null;
      $cargo = null;
      $usuarioCreacionId = null;
      $documentoUsuario = $request->user()?->persona?->PerNumDoc;
      if ($documentoUsuario) {
        $usuarioCreacionId = PrsPersonas::query()
          ->where('numero_documento', $documentoUsuario)
          ->value('id');
      }

    try {
      $limiteDuplicado = now()->subMinutes(5);
      $ultimoEvento = PrsHuellaEventos::query()
        ->where('identificacion', $payload['identificacion'])
        ->where('tipo', 2)
        ->orderByDesc('fecha_creacion')
        ->first();

      $fechaUltimoEvento = $ultimoEvento?->fecha_creacion
        ? Carbon::parse($ultimoEvento->fecha_creacion)
        : null;
      $esEventoRepetidoEnVentana = $ultimoEvento
        && (int) $ultimoEvento->evento === (int) $payload['evento']
        && $fechaUltimoEvento
        && $fechaUltimoEvento->greaterThanOrEqualTo($limiteDuplicado);

      if ($esEventoRepetidoEnVentana) {
        return response()->json([
          'ok' => false,
          'error' => 'Ya existe un registro reciente para este evento. Intenta nuevamente en unos minutos.',
        ], 422);
      }

      $contratoPersona = PerContratoPersona::query()
        ->where('identificacion', $payload['identificacion'])
        ->where('estborrado', 0)
        ->whereHas('perEmpresaPersonas', function ($query) {
          $query->where('activo', 1)
            ->where('estborrado', 0)
            ->whereNull('fecfin')
            ->whereIn('tp_id', [11]);
        })
        ->first();

      if (!$contratoPersona) {
        $this->huelleroLogger()->warning('Huellero evento conductor contrato no encontrado', [
          'identificacion' => $payload['identificacion'],
        ]);
        return response()->json([
          'ok' => false,
          'error' => 'El conductor no fue encontrado o no esta disponible.',
        ], 422);
      }

      $nombre = $contratoPersona->nombreCompleto();
      $cargoDetalle = $contratoPersona->cargoDetallado();
      if ($cargoDetalle) {
        $cargo = $cargoDetalle->descripcion
          ?? $cargoDetalle->nombre
          ?? $cargoDetalle->cargo
          ?? $cargoDetalle->codigo
          ?? null;
      }

      $evento = new PrsHuellaEventos();
      $evento->evento = (int) $payload['evento'];
      $evento->descripcion = ((int) $payload['evento'] === 4)
        ? 'regreso de descanso'
        : 'salida a descanso';
      $evento->tipo = 2;
      $evento->identificacion = $payload['identificacion'];
      $fecha = isset($payload['fecha']) ? Carbon::parse($payload['fecha']) : now();
      $evento->fecha_creacion = $fecha;
      $evento->usuario_creacion = $usuarioCreacionId;
      $evento->llegada_tarde = 0;
      $evento->save();
    } catch (Throwable $e) {
      $this->huelleroLogger()->warning('Huellero evento conductor error', [
        'identificacion' => $payload['identificacion'] ?? null,
        'message' => $e->getMessage(),
      ]);
      return response()->json([
        'ok' => false,
        'error' => 'No se pudo guardar el evento.',
      ], 500);
    }

    return response()->json([
      'ok' => true,
      'data' => [
        'id' => $evento->getKey(),
        'nombre' => $nombre,
        'cargo' => $cargo,
      ],
    ]);
  }

  public function ultimoEventoEmpleado(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'identificacion' => ['required', 'string', 'max:50'],
    ]);

    if ($validator->fails()) {
      return response()->json([
        'ok' => false,
        'error' => $validator->errors()->first(),
      ], 422);
    }

    $identificacion = $validator->validated()['identificacion'];

    try {
      $evento = PrsHuellaEventos::query()
        ->where('identificacion', $identificacion)
        ->where('tipo', 1)
        ->whereIn('evento', [1, 2])
        ->orderBy('fecha_creacion', 'desc')
        ->first();
    } catch (Throwable $e) {
      $this->huelleroLogger()->error('Huellero ultimo evento empleado error', [
        'identificacion' => $identificacion,
        'message' => $e->getMessage(),
      ]);
      return response()->json([
        'ok' => false,
        'error' => 'No se pudo consultar el ultimo evento.',
      ], 500);
    }

    if (!$evento) {
      return response()->json([
        'ok' => true,
        'data' => null,
      ]);
    }

    $fecha = $evento->fecha_creacion;
    try {
      if ($fecha instanceof Carbon) {
        $fecha = $fecha->toIso8601String();
      } elseif ($fecha) {
        $fecha = Carbon::parse($fecha)->toIso8601String();
      }
    } catch (Throwable $e) {
      $fecha = $evento->fecha_creacion;
    }

    return response()->json([
      'ok' => true,
      'data' => [
        'evento' => (int) $evento->evento,
        'descripcion' => $evento->descripcion,
        'identificacion' => (string) $evento->identificacion,
        'fecha' => $fecha,
      ],
    ]);
  }

  public function ultimosEventosEmpleado()
  {
    try {
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
    } catch (Throwable $e) {
      $this->huelleroLogger()->error('Huellero ultimos eventos empleado error', [
        'message' => $e->getMessage(),
      ]);
      return response()->json([
        'ok' => false,
        'error' => 'No se pudo consultar los ultimos eventos.',
      ], 500);
    }

    return response()->json([
      'ok' => true,
      'data' => $this->mapEventoRows($rows),
    ]);
  }

  public function eventosHoyEmpleadoPorIdentificacion(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'identificacion' => ['required', 'string', 'max:20', 'regex:/^\d+$/'],
    ]);

    if ($validator->fails()) {
      return response()->json([
        'ok' => false,
        'error' => 'Identificacion invalida.',
      ], 422);
    }

    $identificacion = preg_replace('/\D+/', '', (string) $request->input('identificacion'));
    $identificacion = trim((string) $identificacion);

    try {
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
    } catch (Throwable $e) {
      $this->huelleroLogger()->error('Huellero eventos hoy empleado error', [
        'identificacion' => $identificacion,
        'message' => $e->getMessage(),
      ]);
      return response()->json([
        'ok' => false,
        'error' => 'No se pudo consultar los registros de hoy.',
      ], 500);
    }

    return response()->json([
      'ok' => true,
      'identificacion' => $identificacion,
      'data' => $this->mapEventoRows($rows),
    ]);
  }

  public function ultimoEventoConductor(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'identificacion' => ['required', 'string', 'max:50'],
    ]);

    if ($validator->fails()) {
      return response()->json([
        'ok' => false,
        'error' => $validator->errors()->first(),
      ], 422);
    }

    $identificacion = $validator->validated()['identificacion'];

    try {
      $evento = PrsHuellaEventos::query()
        ->where('identificacion', $identificacion)
        ->where('tipo', 2)
        ->whereIn('evento', [3, 4])
        ->orderBy('fecha_creacion', 'desc')
        ->first();
    } catch (Throwable $e) {
      $this->huelleroLogger()->error('Huellero ultimo evento conductor error', [
        'identificacion' => $identificacion,
        'message' => $e->getMessage(),
      ]);
      return response()->json([
        'ok' => false,
        'error' => 'No se pudo consultar el ultimo evento.',
      ], 500);
    }

    if (!$evento) {
      return response()->json([
        'ok' => true,
        'data' => null,
      ]);
    }

    $fecha = $evento->fecha_creacion;
    try {
      if ($fecha instanceof Carbon) {
        $fecha = $fecha->toIso8601String();
      } elseif ($fecha) {
        $fecha = Carbon::parse($fecha)->toIso8601String();
      }
    } catch (Throwable $e) {
      $fecha = $evento->fecha_creacion;
    }

    return response()->json([
      'ok' => true,
      'data' => [
        'evento' => (int) $evento->evento,
        'descripcion' => $evento->descripcion,
        'identificacion' => (string) $evento->identificacion,
        'fecha' => $fecha,
      ],
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
        } catch (Throwable $e) {
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
