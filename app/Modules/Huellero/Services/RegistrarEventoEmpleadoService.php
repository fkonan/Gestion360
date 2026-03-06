<?php

namespace App\Modules\Huellero\Services;

use App\Models\GESTIONADMIN\Notificaciones;
use App\Models\GESTIONADMIN\Persona;
use App\Models\User;
use App\Modules\GestionRRHH\Models\PerContratoPersona;
use App\Modules\GestionRRHH\Models\PerPersonas;
use App\Modules\Huellero\Models\PerPersonasEventos;
use App\Modules\Huellero\Models\PrsCargos;
use App\Modules\Huellero\Models\PrsHuellaEventos;
use App\Modules\Huellero\Models\PrsPersonas;
use App\Services\Asistencia\DecidirEventoService;
use App\Services\Asistencia\DecisionEventoDTO;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class RegistrarEventoEmpleadoService
{
  public function __construct(private readonly DecidirEventoService $decidirEventoService)
  {
  }

  public function registrar(
    string $identificacion,
    ?int $eventoManual = null,
    ?Carbon $fechaEvento = null,
    ?string $documentoUsuario = null,
    ?int $usuarioNotificacion = null,
    ?string $origen = 'huella'
  ): array {
    $identificacion = trim($identificacion);
    $origenEvento = $this->normalizarOrigen($origen);
    if ($identificacion === '') {
      $this->decisionLogger()->warning('Asistencia evento rechazado', [
        'identificacion' => $identificacion,
        'motivo' => 'Identificacion invalida.',
        'origen' => $origenEvento,
      ]);

      return $this->respuestaRechazada('Identificacion invalida.');
    }

    $contratoPersona = $this->buscarContratoPersona($identificacion);
    if (!$contratoPersona) {
      $this->decisionLogger()->warning('Asistencia evento rechazado', [
        'identificacion' => $identificacion,
        'motivo' => 'La persona no fue encontrada o no esta disponible.',
        'origen' => $origenEvento,
      ]);

      return $this->respuestaRechazada('La persona no fue encontrada o no esta disponible.');
    }

    $nombre = $contratoPersona->nombreCompleto();
    $cargoNombre = $this->resolverNombreCargo($contratoPersona);
    $cargoId = $cargoNombre ? $this->resolverCargoId($cargoNombre) : null;
    $tipo = ((int) ($contratoPersona->perEmpresaPersonas?->tp_id ?? 0) === 11) ? 2 : 1;
    $fecha = $fechaEvento ? $fechaEvento->copy() : now();

    [$usuarioCreacionId, $usuarioPerPersonasId] = $this->resolverUsuariosCreacion($documentoUsuario);

    try {
      return DB::connection('oracle-360')->transaction(function () use (
        $identificacion,
        $eventoManual,
        $cargoId,
        $tipo,
        $fecha,
        $usuarioCreacionId,
        $usuarioPerPersonasId,
        $usuarioNotificacion,
        $nombre,
        $cargoNombre,
        $origenEvento
      ) {
        $this->bloquearEventosDelDia($identificacion);

        $decision = $this->resolverDecision(
          $identificacion,
          $eventoManual,
          $cargoId,
          $fecha
        );

        if (!$decision->esOk()) {
          $this->decisionLogger()->warning('Asistencia evento rechazado', [
            'identificacion' => $identificacion,
            'motivo' => $decision->motivo,
            'evento_manual' => $eventoManual,
            'cargo_id' => $decision->cargoId,
            'cargo' => $cargoNombre,
            'horario_cargo_id' => $decision->horarioCargoId,
            'flags' => [
              'llegada_tarde' => $decision->llegadaTarde,
              'cargo_especial' => $decision->cargoEspecial,
            ],
            'fecha_evento' => $fecha->format('Y-m-d H:i:s'),
            'trace' => $decision->trace,
            'origen' => $origenEvento,
          ]);

          return $this->respuestaRechazada(
            $decision->motivo ?? 'No se pudo registrar el evento.',
            $decision->cargoId,
            $decision->llegadaTarde,
            $decision->cargoEspecial,
            $decision->horarioCargoId,
            $nombre,
            $cargoNombre
          );
        }

        $eventoCodigo = (int) ($decision->evento ?? 0);
        $descripcionEvento = $this->descripcionEventoPorCodigo($eventoCodigo, $origenEvento);

        $evento = new PrsHuellaEventos();
        $evento->evento = $eventoCodigo;
        $evento->descripcion = $descripcionEvento;
        $evento->tipo = $tipo;
        $evento->identificacion = $identificacion;
        $evento->fecha_creacion = $fecha;
        $evento->usuario_creacion = $usuarioCreacionId;
        $evento->horario_cargo_id = $decision->horarioCargoId;
        $evento->llegada_tarde = $decision->llegadaTarde ? 1 : 0;
        $evento->save();

        $this->registrarEventoPerPersonas(
          $identificacion,
          $eventoCodigo,
          $fecha,
          $usuarioPerPersonasId
        );

        $this->registrarNotificacionEventoEmpleado(
          $identificacion,
          $eventoCodigo,
          $fecha,
          $usuarioNotificacion,
          $decision->llegadaTarde
        );

        $this->decisionLogger()->info('Asistencia evento registrado', [
          'identificacion' => $identificacion,
          'evento' => $eventoCodigo,
          'evento_manual' => $eventoManual,
          'cargo_id' => $decision->cargoId,
          'cargo' => $cargoNombre,
          'horario_cargo_id' => $decision->horarioCargoId,
          'flags' => [
            'llegada_tarde' => $decision->llegadaTarde,
            'cargo_especial' => $decision->cargoEspecial,
          ],
          'descripcion' => $descripcionEvento,
          'fecha_evento' => $fecha->format('Y-m-d H:i:s'),
          'trace' => $decision->trace,
          'origen' => $origenEvento,
        ]);

        return [
          'ok' => true,
          'http_status' => 200,
          'status' => DecisionEventoDTO::STATUS_OK,
          'evento' => $eventoCodigo,
          'motivo' => null,
          'horario_cargo_id' => $decision->horarioCargoId,
          'cargo_id' => $decision->cargoId,
          'flags' => [
            'llegada_tarde' => $decision->llegadaTarde,
            'cargo_especial' => $decision->cargoEspecial,
          ],
          'fecha_evento' => $fecha->toIso8601String(),
          'data' => [
            'id' => $evento->getKey(),
            'nombre' => $nombre,
            'cargo' => $cargoNombre,
          ],
          'origen' => $origenEvento,
        ];
      });
    } catch (Throwable $e) {
      $this->huelleroLogger()->warning('Huellero registro evento empleado error', [
        'identificacion' => $identificacion,
        'message' => $e->getMessage(),
      ]);
      $this->decisionLogger()->error('Asistencia evento error', [
        'identificacion' => $identificacion,
        'evento_manual' => $eventoManual,
        'cargo_id' => $cargoId,
        'message' => $e->getMessage(),
        'origen' => $origenEvento,
      ]);

      return [
        'ok' => false,
        'http_status' => 500,
        'status' => 'ERROR',
        'evento' => null,
        'motivo' => 'No se pudo guardar el evento.',
        'horario_cargo_id' => null,
        'cargo_id' => $cargoId,
        'flags' => [
          'llegada_tarde' => false,
          'cargo_especial' => ((int) $cargoId === 3),
        ],
        'error' => 'No se pudo guardar el evento.',
        'origen' => $origenEvento,
      ];
    }
  }

  private function resolverDecision(
    string $identificacion,
    ?int $eventoManual,
    ?int $cargoId,
    Carbon $fecha
  ): DecisionEventoDTO {
    if ($eventoManual !== null) {
      if (!in_array($eventoManual, [1, 2], true)) {
        return DecisionEventoDTO::rechazado(
          'Tipo de evento invalido.',
          $cargoId,
          ((int) $cargoId === 3),
          ['modo' => 'manual', 'evento_manual' => $eventoManual]
        );
      }

      return DecisionEventoDTO::ok(
        $eventoManual,
        null,
        $cargoId,
        false,
        ((int) $cargoId === 3),
        null,
        ['modo' => 'manual', 'evento_manual' => $eventoManual]
      );
    }

    if (!$cargoId) {
      return DecisionEventoDTO::rechazado(
        'No se pudo resolver el cargo para asignar horarios.',
        null,
        false,
        ['modo' => 'automatico', 'cargo_resuelto' => false]
      );
    }

    return $this->decidirEventoService->decidir($identificacion, $cargoId, $fecha->copy());
  }

  private function buscarContratoPersona(string $identificacion): ?PerContratoPersona
  {
    return PerContratoPersona::query()
      ->where('identificacion', $identificacion)
      ->where('estborrado', 0)
      ->whereHas('perEmpresaPersonas', function ($query) {
        $query->where('activo', 1)
          ->where('estborrado', 0)
          ->whereNull('fecfin')
          ->whereIn('tp_id', [1, 11]);
      })
      ->first();
  }

  private function resolverNombreCargo(PerContratoPersona $contratoPersona): ?string
  {
    $cargoDetalle = $contratoPersona->cargoDetallado();
    if (!$cargoDetalle) {
      return null;
    }

    return $cargoDetalle->descripcion
      ?? $cargoDetalle->nombre
      ?? $cargoDetalle->cargo
      ?? $cargoDetalle->codigo
      ?? null;
  }

  private function resolverCargoId(string $cargoNombre): ?int
  {
    $normalizado = $this->normalizarTexto($cargoNombre);
    if ($normalizado === '') {
      return null;
    }

    $cargo = PrsCargos::query()
      ->whereRaw('UPPER(nombre) = ?', [$normalizado])
      ->orderBy('id')
      ->first(['id', 'nombre']);

    if ($cargo) {
      return (int) $cargo->id;
    }

    $cargos = PrsCargos::query()
      ->orderBy('id')
      ->get(['id', 'nombre']);

    $match = $cargos->first(function ($item) use ($normalizado) {
      return $this->normalizarTexto((string) ($item->nombre ?? '')) === $normalizado;
    });

    return $match ? (int) $match->id : null;
  }

  private function normalizarTexto(string $valor): string
  {
    $compactado = preg_replace('/\s+/', ' ', trim($valor));

    return mb_strtoupper((string) $compactado, 'UTF-8');
  }

  private function resolverUsuariosCreacion(?string $documentoUsuario): array
  {
    if (!$documentoUsuario) {
      return [null, null];
    }

    $usuarioCreacionId = PrsPersonas::query()
      ->where('numero_documento', $documentoUsuario)
      ->value('id');

    $usuarioPerPersonasId = PerPersonas::query()
      ->where('identificacion', $documentoUsuario)
      ->value('id');

    return [
      $usuarioCreacionId ? (string) $usuarioCreacionId : null,
      $usuarioPerPersonasId ? (int) $usuarioPerPersonasId : null,
    ];
  }

  private function bloquearEventosDelDia(string $identificacion): void
  {
    PrsHuellaEventos::query()
      ->where('identificacion', $identificacion)
      ->whereRaw('fecha_creacion >= TRUNC(SYSDATE) AND fecha_creacion < TRUNC(SYSDATE) + 1')
      ->lockForUpdate()
      ->get(['id']);
  }

  private function descripcionEventoPorCodigo(int $eventoCodigo, string $origen): string
  {
    $accion = $eventoCodigo === 2 ? 'ingreso' : 'salida';

    return $accion . ' de personal por ' . $origen;
  }

  private function registrarEventoPerPersonas(
    string $identificacion,
    int $eventoCodigo,
    Carbon $fechaEvento,
    ?int $usuarioPerPersonasId
  ): void {
    $personaId = PerPersonas::query()
      ->where('identificacion', $identificacion)
      ->value('id');

    if (!$personaId) {
      throw new \RuntimeException('No se encontro la persona en PER_PERSONAS para registrar PER_PERSONASEVENTOS.');
    }

    $esEntrada = $eventoCodigo === 2;
    $codigoEvento = $esEntrada ? 49 : 50;
    $anotacion = $esEntrada ? 'ENTRADA POR HUELLERO' : 'SALIDA POR HUELLERO';
    $fechaSistema = now();

    $eventoPersona = new PerPersonasEventos();
    $eventoPersona->pe_id = (int) $personaId;
    $eventoPersona->fechaevento = $fechaEvento;
    $eventoPersona->evento = $codigoEvento;
    $eventoPersona->anotacion = $anotacion;
    $eventoPersona->fecmodifica = $fechaSistema;
    $eventoPersona->usrmodifica = $usuarioPerPersonasId;
    $eventoPersona->rolmodifica = 60;
    $eventoPersona->empmodifica = 6761;
    $eventoPersona->estborrado = 0;
    $eventoPersona->feccreacion = $fechaSistema;
    $eventoPersona->usrcreacion = $usuarioPerPersonasId;
    $eventoPersona->empcreacion = 6761;
    $eventoPersona->tiporegistro = 0;
    $eventoPersona->save();
  }

  private function registrarNotificacionEventoEmpleado(
    string $identificacion,
    int $evento,
    ?Carbon $fecha = null,
    ?int $usuarioNotificacion = null,
    bool $llegadaTarde = false
  ): bool {
    try {
      $persona = Persona::query()
        ->where('PerNumDoc', $identificacion)
        ->first();

      if (!$persona) {
        return false;
      }

      $usuario = User::query()
        ->where('idPersona', $persona->IdPersona)
        ->first();

      if (!$usuario) {
        return false;
      }

      $isSalida = $evento === 1;
      $titulo = $isSalida
        ? 'Salida registrada'
        : ($llegadaTarde ? 'Ingreso tardio registrado' : 'Ingreso registrado');
      $fechaEvento = $fecha ? $fecha->format('h:i A d/m/Y') : now()->format('h:i A d/m/Y');
      $bodyPush = $isSalida
        ? 'Hasta luego. Tu salida se registro a las ' . $fechaEvento . '.'
        : 'Bienvenido. Tu ingreso se registro a las ' . $fechaEvento . '.';
      $destino = json_encode(['usuarios' => [(int) $usuario->IdUsuario]]);

      Notificaciones::create([
        'titulo' => $titulo,
        'bodyPush' => $bodyPush,
        'usuarioCrea' => $usuarioNotificacion ?? auth()->user()?->IdUsuario ?? null,
        'destino' => $destino,
        'privacidad' => 'privada',
        'createdBy' => 'Gestion360',
      ]);

      return true;
    } catch (Throwable $e) {
      $this->huelleroLogger()->error('Huellero notificacion evento empleado error', [
        'identificacion' => $identificacion,
        'message' => $e->getMessage(),
      ]);

      return false;
    }
  }

  private function respuestaRechazada(
    string $motivo,
    ?int $cargoId = null,
    bool $llegadaTarde = false,
    bool $cargoEspecial = false,
    ?int $horarioCargoId = null,
    ?string $nombre = null,
    ?string $cargoNombre = null
  ): array {
    return [
      'ok' => false,
      'http_status' => 422,
      'status' => DecisionEventoDTO::STATUS_RECHAZADO,
      'evento' => null,
      'motivo' => $motivo,
      'horario_cargo_id' => $horarioCargoId,
      'cargo_id' => $cargoId,
      'flags' => [
        'llegada_tarde' => $llegadaTarde,
        'cargo_especial' => $cargoEspecial,
      ],
      'error' => $motivo,
      'data' => [
        'nombre' => $nombre,
        'cargo' => $cargoNombre,
      ],
    ];
  }

  private function normalizarOrigen(?string $origen): string
  {
    $valor = strtolower(trim((string) $origen));
    if ($valor === 'camara') {
      return 'camara';
    }

    return 'huella';
  }

  private function huelleroLogger()
  {
    return Log::build([
      'driver' => 'daily',
      'path' => storage_path('logs/huellero/huellero.log'),
      'days' => 7,
    ]);
  }

  private function decisionLogger()
  {
    return Log::build([
      'driver' => 'daily',
      'path' => storage_path('logs/huellero/asistencia_decisiones.log'),
      'days' => 30,
    ]);
  }
}
