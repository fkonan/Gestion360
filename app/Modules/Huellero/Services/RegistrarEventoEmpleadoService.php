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
use App\Services\Asistencia\NovedadAsistenciaService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class RegistrarEventoEmpleadoService
{
  private const MINUTOS_ANTIDUPLICADO_EVENTO_MANUAL_DEFAULT = 2;
  private const MINUTOS_ANTIDUPLICADO_OVERRIDE_NOVEDAD_DEFAULT = 25;
  private const MINUTOS_NOVEDAD_ANTES_DEFAULT = 5;
  private const MINUTOS_NOVEDAD_DESPUES_DEFAULT = 20;

  private array $cargoIdPorCargoCentroCache = [];
  private array $cargoIdPorNombreCache = [];
  private ?array $catalogoCargosCache = null;
  private array $usuariosCreacionCache = [];
  private NovedadAsistenciaService $novedadAsistenciaService;

  public function __construct(
    private readonly DecidirEventoService $decidirEventoService,
    ?NovedadAsistenciaService $novedadAsistenciaService = null
  )
  {
    $this->novedadAsistenciaService = $novedadAsistenciaService ?? new NovedadAsistenciaService();
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
    $cargoDetalle = $contratoPersona->cargoDetallado();
    $cargoNombre = $this->resolverNombreCargo($cargoDetalle);
    $centroCostoDescripcion = $this->resolverCentroCostoDescripcion($cargoDetalle);
    $cargoId = $cargoNombre
      ? $this->resolverCargoId($cargoNombre, $centroCostoDescripcion)
      : null;
    $tipo = ((int) ($contratoPersona->perEmpresaPersonas?->tp_id ?? 0) === 11) ? 2 : 1;
    $fecha = $fechaEvento ? $fechaEvento->copy() : now();

    [$usuarioCreacionId, $usuarioPerPersonasId] = $this->resolverUsuariosCreacion($documentoUsuario);

    try {
      $resultado = DB::connection('oracle-360')->transaction(function () use (
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
          $this->decisionLogger()->warning('Asistencia evento rechazado', $this->contextoDecisionLog(
            identificacion: $identificacion,
            fecha: $fecha,
            origenEvento: $origenEvento,
            eventoManual: $eventoManual,
            cargoId: $decision->cargoId,
            cargoNombre: $cargoNombre,
            horarioCargoId: $decision->horarioCargoId,
            flags: [
              'llegada_tarde' => $decision->llegadaTarde,
              'cargo_especial' => $decision->cargoEspecial,
            ],
            evento: $decision->evento,
            motivo: $decision->motivo,
            descripcion: null,
            trace: $decision->trace
          ));

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

        $esOverrideNovedad = $this->esDecisionPorOverrideNovedad($decision);
        $llegadaTarde = $esOverrideNovedad ? false : $decision->llegadaTarde;

        $eventoCodigo = (int) ($decision->evento ?? 0);
        $descripcionEvento = $this->descripcionEvento($eventoCodigo, $origenEvento, $decision);

        $evento = new PrsHuellaEventos();
        $evento->evento = $eventoCodigo;
        $evento->descripcion = $descripcionEvento;
        $evento->tipo = $tipo;
        $evento->identificacion = $identificacion;
        $evento->fecha_creacion = $fecha;
        $evento->usuario_creacion = $usuarioCreacionId;
        $evento->horario_cargo_id = $decision->horarioCargoId;
        $evento->llegada_tarde = $llegadaTarde ? 1 : 0;
        $evento->save();

        $this->registrarEventoPerPersonas(
          $identificacion,
          $eventoCodigo,
          $fecha,
          $usuarioPerPersonasId
        );

        $this->decisionLogger()->info('Asistencia evento registrado', $this->contextoDecisionLog(
          identificacion: $identificacion,
          fecha: $fecha,
          origenEvento: $origenEvento,
          eventoManual: $eventoManual,
          cargoId: $decision->cargoId,
          cargoNombre: $cargoNombre,
          horarioCargoId: $decision->horarioCargoId,
          flags: [
            'llegada_tarde' => $llegadaTarde,
            'cargo_especial' => $decision->cargoEspecial,
          ],
          evento: $eventoCodigo,
          motivo: null,
          descripcion: $descripcionEvento,
          trace: $decision->trace
        ));

        return [
          'ok' => true,
          'http_status' => 200,
          'status' => DecisionEventoDTO::STATUS_OK,
          'evento' => $eventoCodigo,
          'motivo' => null,
          'horario_cargo_id' => $decision->horarioCargoId,
          'cargo_id' => $decision->cargoId,
          'flags' => [
            'llegada_tarde' => $llegadaTarde,
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

      if (($resultado['ok'] ?? false) === true) {
        $resultado['notificacion'] = $this->notificarEventoRegistrado(
          $identificacion,
          (int) ($resultado['evento'] ?? 0),
          $fecha,
          $usuarioNotificacion,
          (bool) ($resultado['flags']['llegada_tarde'] ?? false),
          $origenEvento
        );
      }

      return $resultado;
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

      $minutosAntiduplicadoManual = $this->obtenerMinutosAntiduplicadoEventoManual();
      if ($this->existeEventoManualReciente(
        $identificacion,
        $eventoManual,
        $fecha,
        $minutosAntiduplicadoManual
      )) {
        $ventanaTexto = $this->formatearVentanaAntiduplicado($minutosAntiduplicadoManual);
        $motivoDuplicado = $eventoManual === 2
          ? 'ya existe un ingreso registrado recientemente. espere ' . $ventanaTexto . ' antes de repetir el mismo evento'
          : 'ya existe una salida registrada recientemente. espere ' . $ventanaTexto . ' antes de repetir el mismo evento';

        return DecisionEventoDTO::rechazado(
          $motivoDuplicado,
          $cargoId,
          ((int) $cargoId === 3),
          [
            'modo' => 'manual',
            'evento_manual' => $eventoManual,
            'antiduplicado_minutos' => $minutosAntiduplicadoManual,
          ]
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

    $decisionBase = $this->decidirEventoService->decidir($identificacion, $cargoId, $fecha->copy());
    $overrideNovedad = $this->novedadAsistenciaService->resolverEventoPermitido($identificacion, $fecha->copy());
    if (($overrideNovedad['aplica'] ?? false) !== true) {
      $overrideNovedad = $this->resolverIngresoFlexiblePostSalidaNovedad($identificacion, $fecha->copy());
      if (($overrideNovedad['aplica'] ?? false) !== true) {
        return $decisionBase;
      }
    }

    $eventoOverride = (int) ($overrideNovedad['evento'] ?? 0);
    if (!in_array($eventoOverride, [1, 2], true)) {
      return $decisionBase;
    }

    if ($eventoOverride === 2) {
      $hayIngresoAbierto = $this->decidirEventoService->resolverHorarioParaSalidaPorNovedad(
        $identificacion,
        $cargoId,
        $fecha->copy()
      );

      if (($hayIngresoAbierto['ok'] ?? false) === true) {
        return DecisionEventoDTO::rechazado(
          'ya existe un ingreso abierto; debe marcar salida antes de registrar otro ingreso',
          $cargoId,
          ((int) $cargoId === 3),
          $this->traceDecisionNovedad(
            $decisionBase->trace,
            $overrideNovedad,
            'rechazado_novedad_ingreso_duplicado_abierto',
            [
              'detalle_rechazo' => 'Se intento ingreso por novedad con una jornada ya abierta.',
              'ingreso_abierto_resolucion' => $hayIngresoAbierto,
            ]
          )
        );
      }
    }

    if ($this->existeEventoRecientePorOverrideNovedad($identificacion, $eventoOverride, $fecha)) {
      $mensajeDuplicado = $eventoOverride === 2
        ? 'ya existe un ingreso registrado recientemente por novedad'
        : 'ya existe una salida registrada recientemente por novedad. Para ingreso espere la ventana de fecha fin de la novedad.';

      return DecisionEventoDTO::rechazado(
        $mensajeDuplicado,
        $cargoId,
        ((int) $cargoId === 3),
        $this->traceDecisionNovedad(
          $decisionBase->trace,
          $overrideNovedad,
          'rechazado_novedad_evento_reciente',
          ['detalle_rechazo' => $mensajeDuplicado]
        )
      );
    }

    if ($eventoOverride === 1) {
      $resolucionSalida = $this->decidirEventoService->resolverHorarioParaSalidaPorNovedad(
        $identificacion,
        $cargoId,
        $fecha->copy()
      );

      if (($resolucionSalida['ok'] ?? false) !== true) {
        return DecisionEventoDTO::rechazado(
          'novedad aprobada en fecha inicio, pero no hay ingreso abierto para cerrar',
          $cargoId,
          ((int) $cargoId === 3),
          $this->traceDecisionNovedad(
            $decisionBase->trace,
            $overrideNovedad,
            'rechazado_novedad_salida_sin_ingreso_abierto',
            [
              'detalle_rechazo' => 'Novedad en fecha inicio sin ingreso abierto para cierre consistente.',
              'override_salida_resolucion' => $resolucionSalida,
            ]
          )
        );
      }

      return DecisionEventoDTO::ok(
        1,
        (int) $resolucionSalida['horario_cargo_id'],
        $cargoId,
        false,
        ((int) $cargoId === 3),
        'salida por novedad aprobada',
        $this->traceDecisionNovedad(
          $decisionBase->trace,
          $overrideNovedad,
          'ok_salida_por_novedad_fecha_inicio',
          [
            'horario_seleccionado_id' => (int) $resolucionSalida['horario_cargo_id'],
            'override_salida_resolucion' => $resolucionSalida,
          ]
        )
      );
    }

    $resolucionIngreso = $this->decidirEventoService->resolverHorarioParaIngresoPorNovedad(
      $cargoId,
      $fecha->copy()
    );

    if (isset($overrideNovedad['horario_cargo_id']) && (int) $overrideNovedad['horario_cargo_id'] > 0) {
      $resolucionIngreso = [
        'ok' => true,
        'horario_cargo_id' => (int) $overrideNovedad['horario_cargo_id'],
        'criterio' => 'horario_desde_salida_novedad',
      ];
    }

    if (($resolucionIngreso['ok'] ?? false) !== true) {
      return DecisionEventoDTO::rechazado(
        'novedad aprobada en fecha fin, pero no se encontro horario aplicable para ingreso',
        $cargoId,
        ((int) $cargoId === 3),
        $this->traceDecisionNovedad(
          $decisionBase->trace,
          $overrideNovedad,
          'rechazado_novedad_ingreso_sin_horario',
          [
            'detalle_rechazo' => 'Novedad en fecha fin sin horario valido para asociar el ingreso.',
            'override_ingreso_resolucion' => $resolucionIngreso,
          ]
        )
      );
    }

    return DecisionEventoDTO::ok(
      2,
      (int) $resolucionIngreso['horario_cargo_id'],
      $cargoId,
      false,
      ((int) $cargoId === 3),
      'entrada por novedad aprobada',
      $this->traceDecisionNovedad(
        $decisionBase->trace,
        $overrideNovedad,
        'ok_ingreso_por_novedad_fecha_fin',
        [
          'horario_seleccionado_id' => (int) $resolucionIngreso['horario_cargo_id'],
          'override_ingreso_resolucion' => $resolucionIngreso,
        ]
      )
    );
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

  private function resolverNombreCargo(mixed $cargoDetalle): ?string
  {
    if (!$cargoDetalle) {
      return null;
    }

    return $cargoDetalle->descripcion
      ?? $cargoDetalle->nombre
      ?? $cargoDetalle->cargo
      ?? $cargoDetalle->codigo
      ?? null;
  }

  private function resolverCentroCostoDescripcion(mixed $cargoDetalle): ?string
  {
    if (!$cargoDetalle) {
      return null;
    }

    $descripcion = $cargoDetalle->centro_costo_descripcion ?? null;

    return is_string($descripcion) ? trim($descripcion) : null;
  }

  private function resolverCargoId(string $cargoNombre, ?string $centroCostoDescripcion = null): ?int
  {
    $normalizado = $this->normalizarTexto($cargoNombre);
    if ($normalizado === '') {
      return null;
    }

    $centroNormalizado = $this->formatearNombreCentroCosto($centroCostoDescripcion);
    $cacheKey = $normalizado . '|' . $centroNormalizado;
    if (array_key_exists($cacheKey, $this->cargoIdPorCargoCentroCache)) {
      return $this->cargoIdPorCargoCentroCache[$cacheKey];
    }

    if ($centroNormalizado !== '') {
      $cargoConCentro = PrsCargos::query()
        ->join('PRS_CENTRO_COSTO as pcc', 'pcc.id', '=', 'PRS_CARGOS.centro_costo_id')
        ->whereRaw('UPPER(PRS_CARGOS.nombre) = ?', [$normalizado])
        ->whereRaw('UPPER(pcc.nombre) = ?', [$centroNormalizado])
        ->orderBy('PRS_CARGOS.id')
        ->first(['PRS_CARGOS.id']);

      if ($cargoConCentro) {
        return $this->cargoIdPorCargoCentroCache[$cacheKey] = (int) $cargoConCentro->id;
      }

      $totalCoincidenciasPorNombre = PrsCargos::query()
        ->whereRaw('UPPER(nombre) = ?', [$normalizado])
        ->count();

      if ($totalCoincidenciasPorNombre > 1) {
        return $this->cargoIdPorCargoCentroCache[$cacheKey] = null;
      }
    }

    return $this->cargoIdPorCargoCentroCache[$cacheKey] = $this->resolverCargoIdPorNombre($normalizado);
  }

  private function resolverCargoIdPorNombre(string $cargoNombreNormalizado): ?int
  {
    $normalizado = $cargoNombreNormalizado;
    if (array_key_exists($normalizado, $this->cargoIdPorNombreCache)) {
      return $this->cargoIdPorNombreCache[$normalizado];
    }

    $cargo = PrsCargos::query()
      ->whereRaw('UPPER(nombre) = ?', [$normalizado])
      ->orderBy('id')
      ->first(['id', 'nombre']);

    if ($cargo) {
      return $this->cargoIdPorNombreCache[$normalizado] = (int) $cargo->id;
    }

    if ($this->catalogoCargosCache === null) {
      $this->catalogoCargosCache = PrsCargos::query()
        ->orderBy('id')
        ->get(['id', 'nombre'])
        ->map(function ($item) {
          return [
            'id' => (int) $item->id,
            'nombre_normalizado' => $this->normalizarTexto((string) ($item->nombre ?? '')),
          ];
        })
        ->all();
    }

    $match = collect($this->catalogoCargosCache)->first(function (array $item) use ($normalizado) {
      return $item['nombre_normalizado'] === $normalizado;
    });

    return $this->cargoIdPorNombreCache[$normalizado] = $match ? (int) $match['id'] : null;
  }

  private function formatearNombreCentroCosto(?string $descripcion): string
  {
    $descripcionNormalizada = $this->normalizarTexto((string) $descripcion);
    if ($descripcionNormalizada === '') {
      return '';
    }

    $nombreCentro = preg_replace('/^\d+\s*-\s*/u', '', $descripcionNormalizada);
    $nombreCentro = preg_replace('/\bDE\b/u', ' ', (string) $nombreCentro);
    $nombreCentro = preg_replace('/\s+/', ' ', trim((string) $nombreCentro));
    if ($nombreCentro === '') {
      $nombreCentro = $descripcionNormalizada;
    }

    return mb_substr((string) $nombreCentro, 0, 150, 'UTF-8');
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

    if (array_key_exists($documentoUsuario, $this->usuariosCreacionCache)) {
      return $this->usuariosCreacionCache[$documentoUsuario];
    }

    $usuarioCreacionId = PrsPersonas::query()
      ->where('numero_documento', $documentoUsuario)
      ->value('id');

    $usuarioPerPersonasId = PerPersonas::query()
      ->where('identificacion', $documentoUsuario)
      ->value('id');

    return $this->usuariosCreacionCache[$documentoUsuario] = [
      $usuarioCreacionId ? (string) $usuarioCreacionId : null,
      $usuarioPerPersonasId ? (int) $usuarioPerPersonasId : null,
    ];
  }

  private function notificarEventoRegistrado(
    string $identificacion,
    int $eventoCodigo,
    Carbon $fecha,
    ?int $usuarioNotificacion,
    bool $llegadaTarde,
    string $origenEvento
  ): array {
    if ($origenEvento === 'api' && !app()->runningInConsole()) {
      app()->terminating(function () use (
        $identificacion,
        $eventoCodigo,
        $fecha,
        $usuarioNotificacion,
        $llegadaTarde
      ) {
        $this->registrarNotificacionEventoEmpleado(
          $identificacion,
          $eventoCodigo,
          $fecha,
          $usuarioNotificacion,
          $llegadaTarde
        );
      });

      return [
        'modo' => 'diferida',
        'enviada' => null,
      ];
    }

    $notificacionEnviada = $this->registrarNotificacionEventoEmpleado(
      $identificacion,
      $eventoCodigo,
      $fecha,
      $usuarioNotificacion,
      $llegadaTarde
    );

    return [
      'modo' => 'inmediata',
      'enviada' => $notificacionEnviada,
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

  private function descripcionEvento(int $eventoCodigo, string $origen, DecisionEventoDTO $decision): string
  {
    if ($this->esDecisionPorOverrideNovedad($decision)) {
      return $eventoCodigo === 2
        ? 'entrada por novedad aprobada'
        : 'salida por novedad aprobada';
    }

    return $this->descripcionEventoPorCodigo($eventoCodigo, $origen);
  }

  private function resolverIngresoFlexiblePostSalidaNovedad(string $identificacion, Carbon $fecha): array
  {
    $novedades = DB::connection('oracle-360')
      ->table('EMP_NOVEDADES as n')
      ->leftJoin('EMP_NOVEDADES_TIPO as t', 't.id', '=', 'n.id_tipo_novedad')
      ->where('n.id_persona', $identificacion)
      ->where('n.estado', 'APROBADO')
      ->whereNotNull('n.fecha_inicio')
      ->where('n.fecha_inicio', '<=', $fecha)
      ->orderByDesc('n.fecha_inicio')
      ->get([
        'n.id',
        'n.id_persona',
        'n.id_tipo_novedad',
        'n.fecha_inicio',
        'n.fecha_fin',
        'n.observacion',
        't.descripcion as tipo_descripcion',
      ]);

    if ($novedades->isEmpty()) {
      return [
        'aplica' => false,
      ];
    }

    $desdeEventos = $fecha->copy()->startOfDay()->subDay();
    $hastaEventos = $fecha->copy()->endOfDay();

    $eventos = PrsHuellaEventos::query()
      ->where('identificacion', $identificacion)
      ->whereIn('evento', [1, 2])
      ->whereBetween('fecha_creacion', [$desdeEventos, $hastaEventos])
      ->orderBy('fecha_creacion')
      ->get(['evento', 'horario_cargo_id', 'fecha_creacion', 'descripcion']);

    if ($eventos->isEmpty()) {
      return [
        'aplica' => false,
      ];
    }

    $minAntes = $this->obtenerMinutosNovedadAntes();
    $minDespues = $this->obtenerMinutosNovedadDespues();

    $salidasCandidatas = $eventos
      ->filter(function ($evento) {
        return (int) ($evento->evento ?? 0) === 1;
      })
      ->map(function ($salida) use ($novedades, $minAntes, $minDespues) {
        $fechaSalida = $this->parseFechaEventoSeguro($salida->fecha_creacion ?? null);
        if (!$fechaSalida) {
          return null;
        }

        $novedadAsociada = $novedades->first(function ($novedad) use ($fechaSalida, $minAntes, $minDespues) {
          $fechaInicio = $this->parseFechaEventoSeguro($novedad->fecha_inicio ?? null);
          if (!$fechaInicio) {
            return false;
          }

          return $fechaSalida->betweenIncluded(
            $fechaInicio->copy()->subMinutes($minAntes),
            $fechaInicio->copy()->addMinutes($minDespues)
          );
        });

        $descripcion = mb_strtolower((string) ($salida->descripcion ?? ''), 'UTF-8');
        $marcaNovedadPorDescripcion = str_contains($descripcion, 'novedad');
        $esSalidaPorNovedad = $novedadAsociada !== null || $marcaNovedadPorDescripcion;

        return [
          'evento' => $salida,
          'fecha_salida' => $fechaSalida,
          'novedad' => $novedadAsociada,
          'es_salida_por_novedad' => $esSalidaPorNovedad,
        ];
      })
      ->filter(function ($item) {
        return $item !== null && $item['es_salida_por_novedad'] === true;
      })
      ->sortByDesc(function ($item) {
        return $item['fecha_salida']->timestamp;
      })
      ->values();

    if ($salidasCandidatas->isEmpty()) {
      return [
        'aplica' => false,
      ];
    }

    $salidaCandidata = $salidasCandidatas->first();
    $fechaSalida = $salidaCandidata['fecha_salida'];
    if (!$fechaSalida || $fecha->lt($fechaSalida)) {
      return [
        'aplica' => false,
      ];
    }

    $existeIngresoPosterior = $eventos->contains(function ($evento) use ($fechaSalida) {
      if ((int) ($evento->evento ?? 0) !== 2) {
        return false;
      }

      $fechaEvento = $this->parseFechaEventoSeguro($evento->fecha_creacion ?? null);
      if (!$fechaEvento) {
        return false;
      }

      return $fechaEvento->gt($fechaSalida);
    });

    if ($existeIngresoPosterior) {
      return [
        'aplica' => false,
      ];
    }

    $novedad = $salidaCandidata['novedad'];
    $horarioSugerido = isset($salidaCandidata['evento']->horario_cargo_id)
      ? (int) ($salidaCandidata['evento']->horario_cargo_id ?? 0)
      : 0;

    return [
      'aplica' => true,
      'evento' => 2,
      'ventana' => 'retorno_abierto',
      'novedad_id' => isset($novedad->id) ? (string) $novedad->id : null,
      'id_persona' => isset($novedad->id_persona) ? (string) $novedad->id_persona : $identificacion,
      'tipo_novedad_id' => isset($novedad->id_tipo_novedad) ? (string) $novedad->id_tipo_novedad : null,
      'tipo_novedad' => $novedad->tipo_descripcion ?? null,
      'fecha_inicio' => isset($novedad->fecha_inicio) ? $this->formatearFechaEventoSeguro($novedad->fecha_inicio) : null,
      'fecha_fin' => isset($novedad->fecha_fin) ? $this->formatearFechaEventoSeguro($novedad->fecha_fin) : null,
      'fecha_referencia' => $fechaSalida->format('Y-m-d H:i:s'),
      'observacion' => $novedad->observacion ?? null,
      'retorno_abierto' => true,
      'horario_cargo_id' => $horarioSugerido > 0 ? $horarioSugerido : null,
    ];
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
        $this->huelleroLogger()->warning('Huellero notificacion evento empleado omitida: persona no encontrada', [
          'identificacion' => $identificacion,
        ]);
        return false;
      }

      $usuario = User::query()
        ->where('idPersona', $persona->IdPersona)
        ->first();

      if (!$usuario) {
        $this->huelleroLogger()->warning('Huellero notificacion evento empleado omitida: usuario no encontrado', [
          'identificacion' => $identificacion,
          'id_persona' => $persona->IdPersona,
        ]);
        return false;
      }

      $isSalida = $evento === 1;
      $titulo = $isSalida
        ? 'Salida registrada'
        : ($llegadaTarde ? 'Se ha registrado un ingreso tardío' : 'Ingreso registrado');
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

  private function contextoDecisionLog(
    string $identificacion,
    Carbon $fecha,
    string $origenEvento,
    ?int $eventoManual,
    ?int $cargoId,
    ?string $cargoNombre,
    ?int $horarioCargoId,
    array $flags,
    ?int $evento,
    ?string $motivo,
    ?string $descripcion,
    array $trace
  ): array {
    $contexto = [
      'identificacion' => $identificacion,
      'evento' => $evento,
      'motivo' => $motivo,
      'evento_manual' => $eventoManual,
      'cargo_id' => $cargoId,
      'cargo' => $cargoNombre,
      'horario_cargo_id' => $horarioCargoId,
      'flags' => $flags,
      'descripcion' => $descripcion,
      'fecha_evento' => $fecha->format('Y-m-d H:i:s'),
      'origen' => $origenEvento,
    ];

    $decision = $this->resumirTraceDecision($trace);
    if ($decision !== []) {
      $contexto['decision'] = $decision;
    }

    return $contexto;
  }

  private function resumirTraceDecision(array $trace): array
  {
    if ($trace === []) {
      return [];
    }

    $resumen = [];

    foreach ([
      'modo',
      'resultado',
      'detalle_rechazo',
      'dia_semana',
      'fecha_hora',
      'ingreso_hoy_existe',
      'salida_hoy_existe',
      'proxima_hora_fin',
      'horario_seleccionado_id',
      'horario_ingreso_abierto_id',
      'hora_fin_horario_abierto',
      'umbral_llegada_tarde',
      'override_novedad_aplicado',
      'override_novedad_evento',
      'override_novedad_ventana',
      'override_novedad_id',
    ] as $campo) {
      if (array_key_exists($campo, $trace)) {
        $resumen[$campo] = $trace[$campo];
      }
    }

    if (isset($trace['bloqueo_reingreso_post_salida']) && is_array($trace['bloqueo_reingreso_post_salida'])) {
      $bloqueo = $trace['bloqueo_reingreso_post_salida'];
      $resumen['bloqueo_reingreso_post_salida'] = [
        'aplica' => (bool) ($bloqueo['aplica'] ?? false),
        'activo' => (bool) ($bloqueo['activo'] ?? false),
        'bloquea_hasta' => $bloqueo['bloquea_hasta'] ?? null,
      ];
    }

    $conteos = [];
    foreach ([
      'horarios_evaluados' => 'horarios',
      'eventos_hoy' => 'eventos_hoy',
      'horarios_ingreso_abierto' => 'ingresos_abiertos',
      'horarios_ingreso_abierto_obsoletos' => 'ingresos_obsoletos',
      'jornadas_activas_ahora' => 'jornadas_activas',
      'candidatos_ingreso_transicion' => 'candidatos_transicion',
      'rangos_ingreso' => 'rangos_ingreso',
    ] as $campo => $alias) {
      if (isset($trace[$campo]) && is_array($trace[$campo])) {
        $conteos[$alias] = count($trace[$campo]);
      }
    }

    if ($conteos !== []) {
      $resumen['conteos'] = $conteos;
    }

    return $resumen;
  }

  private function traceDecisionNovedad(
    array $traceBase,
    array $overrideNovedad,
    string $resultado,
    array $extra = []
  ): array {
    $trace = $traceBase;
    $trace['resultado'] = $resultado;
    $trace['override_novedad_aplicado'] = true;
    $trace['override_novedad_evento'] = (int) ($overrideNovedad['evento'] ?? 0);
    $trace['override_novedad_ventana'] = $overrideNovedad['ventana'] ?? null;
    $trace['override_novedad_id'] = $overrideNovedad['novedad_id'] ?? null;
    $trace['override_novedad'] = $overrideNovedad;

    foreach ($extra as $key => $value) {
      $trace[$key] = $value;
    }

    return $trace;
  }

  private function existeEventoRecientePorOverrideNovedad(
    string $identificacion,
    int $evento,
    Carbon $fecha
  ): bool {
    $minutosAntiduplicado = $this->obtenerMinutosAntiduplicadoOverrideNovedad();
    $fechaInicioVentana = $fecha->copy()->subMinutes($minutosAntiduplicado);
    $fechaFinVentana = $fecha->copy()->addMinutes(1);

    return PrsHuellaEventos::query()
      ->where('identificacion', $identificacion)
      ->where('evento', $evento)
      ->whereBetween('fecha_creacion', [$fechaInicioVentana, $fechaFinVentana])
      ->exists();
  }

  private function existeEventoManualReciente(
    string $identificacion,
    int $evento,
    Carbon $fecha,
    int $minutosAntiduplicado
  ): bool {
    $fechaInicioVentana = $fecha->copy()->subMinutes($minutosAntiduplicado);

    return PrsHuellaEventos::query()
      ->where('identificacion', $identificacion)
      ->where('evento', $evento)
      ->where('fecha_creacion', '>', $fechaInicioVentana)
      ->where('fecha_creacion', '<=', $fecha)
      ->exists();
  }

  private function obtenerMinutosAntiduplicadoEventoManual(): int
  {
    $minutos = (int) env(
      'ASISTENCIA_MANUAL_MINUTOS_ANTIDUPLICADO',
      self::MINUTOS_ANTIDUPLICADO_EVENTO_MANUAL_DEFAULT
    );

    return $minutos > 0
      ? $minutos
      : self::MINUTOS_ANTIDUPLICADO_EVENTO_MANUAL_DEFAULT;
  }

  private function formatearVentanaAntiduplicado(int $minutos): string
  {
    return $minutos === 1 ? '1 minuto' : $minutos . ' minutos';
  }

  private function obtenerMinutosAntiduplicadoOverrideNovedad(): int
  {
    $minutos = (int) env(
      'ASISTENCIA_NOVEDAD_MINUTOS_ANTIDUPLICADO',
      self::MINUTOS_ANTIDUPLICADO_OVERRIDE_NOVEDAD_DEFAULT
    );

    return $minutos > 0
      ? $minutos
      : self::MINUTOS_ANTIDUPLICADO_OVERRIDE_NOVEDAD_DEFAULT;
  }

  private function obtenerMinutosNovedadAntes(): int
  {
    $minutos = (int) env('ASISTENCIA_NOVEDAD_MINUTOS_ANTES', self::MINUTOS_NOVEDAD_ANTES_DEFAULT);

    return $minutos >= 0 ? $minutos : self::MINUTOS_NOVEDAD_ANTES_DEFAULT;
  }

  private function obtenerMinutosNovedadDespues(): int
  {
    $minutos = (int) env('ASISTENCIA_NOVEDAD_MINUTOS_DESPUES', self::MINUTOS_NOVEDAD_DESPUES_DEFAULT);

    return $minutos >= 0 ? $minutos : self::MINUTOS_NOVEDAD_DESPUES_DEFAULT;
  }

  private function parseFechaEventoSeguro(mixed $fecha): ?Carbon
  {
    if (!$fecha) {
      return null;
    }

    try {
      return Carbon::parse($fecha);
    } catch (Throwable $e) {
      return null;
    }
  }

  private function formatearFechaEventoSeguro(mixed $fecha): ?string
  {
    $valor = $this->parseFechaEventoSeguro($fecha);

    return $valor ? $valor->format('Y-m-d H:i:s') : null;
  }

  private function esDecisionPorOverrideNovedad(DecisionEventoDTO $decision): bool
  {
    return (bool) ($decision->trace['override_novedad_aplicado'] ?? false);
  }

  private function normalizarOrigen(?string $origen): string
  {
    $valor = strtolower(trim((string) $origen));
    if ($valor === 'camara') {
      return 'camara';
    }
    if ($valor === 'api') {
      return 'api';
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
