<?php

namespace App\Services\Asistencia;

use App\Modules\Huellero\Models\PrsHorariosCargos;
use App\Modules\Huellero\Models\PrsHuellaEventos;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DecidirEventoService
{
  private const EVENTO_SALIDA = 1;
  private const EVENTO_INGRESO = 2;
  private const HORAS_BLOQUEO_REINGRESO_POST_SALIDA_DEFAULT = 3;

  private ?array $cargosEspecialesCache = null;

  public function decidir(string $identificacion, int $cargoId, Carbon $now): DecisionEventoDTO
  {
    $horarios = $this->obtenerHorariosAplicables($cargoId, $now);
    $eventosHoy = $this->obtenerEventosParaDecision($identificacion, $horarios);

    return $this->decidirConDatos($cargoId, $now, $horarios, $eventosHoy);
  }

  public function decidirConDatos(
    int $cargoId,
    Carbon $now,
    Collection $horarios,
    Collection $eventosHoy
  ): DecisionEventoDTO {
    $cargosEspeciales = $this->obtenerCargosEspecialesIds();
    $cargoEspecial = in_array($cargoId, $cargosEspeciales, true);
    $horasBloqueo = $this->obtenerHorasBloqueoReingresoPostSalida();
    $trace = [
      'dia_semana' => $this->diaSemanaIso($now),
      'fecha_hora' => $now->format('Y-m-d H:i:s'),
      'criterios' => [
        'ingreso_rango_jornada' => 'hora_inicio -15 min <= ahora <= hora_fin cuando no hay ingreso abierto',
        'salida_requiere_ingreso_abierto' => true,
        'salida_desde_hora_fin' => true,
        'salida_fuera_de_otra_jornada' => 'para cargos por defecto, no permite salida si ahora cae en otra jornada valida',
        'salida_descarta_jornadas_abiertas_obsoletas' => 'si existe un ingreso posterior en otra jornada, la jornada anterior abierta deja de ser candidata a salida',
        'bloqueo_reingreso_post_salida_cargos' => $cargosEspeciales,
        'bloqueo_reingreso_post_salida_horas' => $horasBloqueo,
        'llegada_tarde_desde' => 'ahora >= hora_inicio + 5 minutos',
      ],
      'horarios_evaluados' => $this->mapHorarios($horarios),
      'eventos_hoy' => $this->mapEventos($eventosHoy),
      'rangos_ingreso' => [],
    ];

    if ($horarios->isEmpty()) {
      $trace['resultado'] = 'rechazado_sin_horarios';

      return DecisionEventoDTO::rechazado('fuera de horarios', $cargoId, $cargoEspecial, $trace);
    }

    $ingresoHoyExiste = $eventosHoy->where('evento', self::EVENTO_INGRESO)->isNotEmpty();
    $salidaHoyExiste = $eventosHoy->where('evento', self::EVENTO_SALIDA)->isNotEmpty();
    $ingresoMismoDiaExiste = $this->existeEventoMismoDiaOIndeterminado($eventosHoy, self::EVENTO_INGRESO, $now);
    $salidaMismoDiaExiste = $this->existeEventoMismoDiaOIndeterminado($eventosHoy, self::EVENTO_SALIDA, $now);
    $trace['ingreso_hoy_existe'] = $ingresoHoyExiste;
    $trace['salida_hoy_existe'] = $salidaHoyExiste;
    $trace['ingreso_mismo_dia_existe'] = $ingresoMismoDiaExiste;
    $trace['salida_mismo_dia_existe'] = $salidaMismoDiaExiste;

    [$horariosConIngresoAbierto, $horariosConIngresoAbiertoObsoletos] = $this->resolverHorariosConIngresoAbierto($horarios, $eventosHoy, $now);
    if ($horariosConIngresoAbiertoObsoletos->isNotEmpty()) {
      $trace['horarios_ingreso_abierto_obsoletos'] = $horariosConIngresoAbiertoObsoletos->map(function ($item) {
        return [
          'horario_cargo_id' => (int) $item['horario']->id,
          'ultimo_ingreso' => $item['ultimo_ingreso'] ? $item['ultimo_ingreso']->format('Y-m-d H:i:s') : null,
          'ingreso_posterior_en_otro_horario' => $item['ingreso_posterior'] ? $item['ingreso_posterior']->format('Y-m-d H:i:s') : null,
          'horario_posterior_id' => $item['horario_posterior_id'] !== null ? (int) $item['horario_posterior_id'] : null,
          'obsoleto_por_ciclo' => (bool) ($item['obsoleto_por_ciclo'] ?? false),
        ];
      })->values()->all();
    }
    if ($horariosConIngresoAbierto->isNotEmpty()) {
      $aplicaBloqueoSalidaPorOtraJornada = $this->aplicaBloqueoSalidaPorOtraJornada($cargoId);
      $jornadasActivasAhora = $this->resolverJornadasActivas($horarios, $now);
      $trace['jornadas_activas_ahora'] = $jornadasActivasAhora
        ->map(function ($item) {
          return [
            'horario_cargo_id' => (int) $item['horario']->id,
            'ventana_desde' => $item['ventana_desde']->format('H:i:s'),
            'hora_inicio' => $item['hora_inicio']->format('H:i:s'),
            'hora_fin' => $item['hora_fin']->format('H:i:s'),
          ];
        })
        ->values()
        ->all();

      $evaluacionesSalida = $horariosConIngresoAbierto
        ->map(function ($horario) use ($now, $jornadasActivasAhora, $aplicaBloqueoSalidaPorOtraJornada) {
          $ventanaHorario = $this->resolverVentanaHorario($now, $horario);
          if (!$ventanaHorario) {
            return null;
          }
          $horaFin = $ventanaHorario['hora_fin'];

          $horarioId = (int) $horario->id;
          $jornadasConflicto = $jornadasActivasAhora
            ->filter(function ($item) use ($horarioId) {
              return (int) $item['horario']->id !== $horarioId;
            })
            ->values();

          return [
            'horario' => $horario,
            'hora_fin' => $horaFin,
            'ya_puede_salir' => $now->greaterThanOrEqualTo($horaFin),
            'bloqueado_por_otra_jornada' => $aplicaBloqueoSalidaPorOtraJornada && $jornadasConflicto->isNotEmpty(),
            'jornadas_conflicto' => $jornadasConflicto,
          ];
        })
        ->filter()
        ->values();

      $trace['horarios_ingreso_abierto'] = $evaluacionesSalida->map(function ($item) {
        return [
          'horario_cargo_id' => (int) $item['horario']->id,
          'hora_fin' => $item['hora_fin']->format('H:i:s'),
          'ya_puede_salir' => $item['ya_puede_salir'],
          'bloqueado_por_otra_jornada' => $item['bloqueado_por_otra_jornada'],
          'jornadas_conflicto' => $item['jornadas_conflicto']
            ->map(function ($conflicto) {
              return [
                'horario_cargo_id' => (int) $conflicto['horario']->id,
                'hora_inicio' => $conflicto['hora_inicio']->format('H:i:s'),
                'hora_fin' => $conflicto['hora_fin']->format('H:i:s'),
              ];
            })
            ->values()
            ->all(),
        ];
      })->values()->all();

      $horariosListosParaSalida = $evaluacionesSalida
        ->filter(function ($item) {
          return $item['ya_puede_salir'] === true;
        })
        ->values();

      $horariosBloqueadosPorOtraJornada = $horariosListosParaSalida
        ->filter(function ($item) {
          return $item['bloqueado_por_otra_jornada'] === true;
        })
        ->values();

      $horariosElegiblesSalida = $horariosListosParaSalida
        ->filter(function ($item) {
          return $item['bloqueado_por_otra_jornada'] === false;
        })
        ->sortByDesc(function ($item) {
          return $item['hora_fin']->timestamp;
        })
        ->values();

      if ($horariosElegiblesSalida->isNotEmpty()) {
        $seleccionado = $horariosElegiblesSalida->first();
        $horarioSalida = $seleccionado['horario'];

        if ($cargoEspecial) {
          if (!$ingresoHoyExiste) {
            $trace['resultado'] = 'rechazado_cargo_especial_sin_ingreso';

            return DecisionEventoDTO::rechazado('no puede salir sin haber ingresado hoy', $cargoId, true, $trace);
          }

          if ($salidaMismoDiaExiste) {
            $trace['resultado'] = 'rechazado_cargo_especial_salida_duplicada';

            return DecisionEventoDTO::rechazado('ya existe salida registrada hoy', $cargoId, true, $trace);
          }
        }

        $trace['resultado'] = 'ok_salida_por_ingreso_abierto';
        $trace['horario_ingreso_abierto_id'] = (int) $horarioSalida->id;
        $trace['hora_fin_horario_abierto'] = $seleccionado['hora_fin']->format('H:i:s');

        return DecisionEventoDTO::ok(
          self::EVENTO_SALIDA,
          (int) $horarioSalida->id,
          $cargoId,
          false,
          $cargoEspecial,
          'ingreso personal huellero',
          $trace
        );
      }

      if ($horariosBloqueadosPorOtraJornada->isNotEmpty()) {
        $trace['detalle_rechazo'] = 'Hay ingreso abierto y cumple hora_fin, pero ahora esta dentro de otra jornada valida.';
        $trace['horarios_bloqueados_por_otra_jornada'] = $horariosBloqueadosPorOtraJornada
          ->map(function ($item) {
            return [
              'horario_cargo_id' => (int) $item['horario']->id,
              'hora_fin' => $item['hora_fin']->format('H:i:s'),
              'jornadas_conflicto' => $item['jornadas_conflicto']
                ->map(function ($conflicto) {
                  return (int) $conflicto['horario']->id;
                })
                ->values()
                ->all(),
            ];
          })
          ->values()
          ->all();

        $horariosConIngresoAbiertoIds = $horariosConIngresoAbierto
          ->map(function ($horario) {
            return (int) $horario->id;
          })
          ->values();

        $candidatosIngresoTransicion = $jornadasActivasAhora
          ->filter(function ($item) use ($horariosConIngresoAbiertoIds) {
            $horarioId = (int) $item['horario']->id;

            return !$horariosConIngresoAbiertoIds->contains($horarioId);
          })
          ->sortByDesc(function ($item) {
            return $item['hora_inicio']->timestamp;
          })
          ->values();

        $trace['candidatos_ingreso_transicion'] = $candidatosIngresoTransicion
          ->map(function ($item) {
            return [
              'horario_cargo_id' => (int) $item['horario']->id,
              'ventana_desde' => $item['ventana_desde']->format('H:i:s'),
              'hora_inicio' => $item['hora_inicio']->format('H:i:s'),
              'hora_fin' => $item['hora_fin']->format('H:i:s'),
            ];
          })
          ->values()
          ->all();

        if ($candidatosIngresoTransicion->isNotEmpty()) {
          $seleccionadoIngreso = $candidatosIngresoTransicion->first();
          $horarioIngreso = $seleccionadoIngreso['horario'];
          $horaInicioIngreso = $seleccionadoIngreso['hora_inicio'];
          $umbralLlegadaTarde = $horaInicioIngreso->copy()->addMinutes(5);
          $llegadaTarde = $now->greaterThanOrEqualTo($umbralLlegadaTarde);
          $trace['resultado'] = 'ok_ingreso_por_transicion_jornada';
          $trace['horario_seleccionado_id'] = (int) $horarioIngreso->id;
          $trace['llegada_tarde'] = $llegadaTarde;
          $trace['umbral_llegada_tarde'] = $umbralLlegadaTarde->format('H:i:s');

          return DecisionEventoDTO::ok(
            self::EVENTO_INGRESO,
            (int) $horarioIngreso->id,
            $cargoId,
            $llegadaTarde,
            $cargoEspecial,
            $llegadaTarde ? 'LLEGADA TARDE' : 'entrada personal huellero',
            $trace
          );
        }

        $trace['resultado'] = 'rechazado_salida_dentro_de_otra_jornada';

        return DecisionEventoDTO::rechazado(
          'no puede marcar salida dentro de otra jornada valida',
          $cargoId,
          $cargoEspecial,
          $trace
        );
      }

      $primerHoraFinPendiente = $evaluacionesSalida
        ->filter(function ($item) {
          return $item['ya_puede_salir'] === false;
        })
        ->map(function ($item) {
          return $item['hora_fin'];
        })
        ->sortBy(function ($hora) {
          return $hora->timestamp;
        })
        ->first();

      $trace['resultado'] = 'rechazado_salida_antes_de_hora_fin';
      $trace['proxima_hora_fin'] = $primerHoraFinPendiente
        ? $primerHoraFinPendiente->format('H:i:s')
        : null;
      $trace['detalle_rechazo'] = 'Hay ingreso abierto, pero aun no cumple hora_fin para salida.';

      return DecisionEventoDTO::rechazado('aun no puede salir', $cargoId, $cargoEspecial, $trace);
    }

    $bloqueoReingreso = $this->resolverBloqueoReingresoPostSalida($cargoId, $eventosHoy, $now);
    $trace['bloqueo_reingreso_post_salida'] = $bloqueoReingreso;

    if (($bloqueoReingreso['activo'] ?? false) === true) {
      $trace['resultado'] = 'rechazado_reingreso_post_salida_cargo_especial';
      $trace['detalle_rechazo'] = 'Cargo con jornadas continuas: no se permite nuevo ingreso dentro de la ventana de bloqueo.';
      $motivo = 'reingreso bloqueado por 7 horas desde la ultima salida';
      if (!empty($bloqueoReingreso['bloquea_hasta'])) {
        $motivo .= ' (habilitado desde ' . $bloqueoReingreso['bloquea_hasta'] . ')';
      }

      return DecisionEventoDTO::rechazado(
        $motivo,
        $cargoId,
        false,
        $trace
      );
    }

    $candidatosIngreso = collect();
    foreach ($horarios as $horario) {
      $ventanaHorario = $this->resolverVentanaHorario($now, $horario);
      if (!$ventanaHorario) {
        continue;
      }
      $horaInicio = $ventanaHorario['hora_inicio'];
      $horaFin = $ventanaHorario['hora_fin'];

      $ventanaDesdeIngreso = $ventanaHorario['ventana_desde_ingreso'];
      $dentroRango = !$now->lt($ventanaDesdeIngreso) && !$now->gt($horaFin);
      $trace['rangos_ingreso'][] = [
        'horario_cargo_id' => (int) $horario->id,
        'ventana_desde' => $ventanaDesdeIngreso->format('H:i:s'),
        'hora_inicio' => $horaInicio->format('H:i:s'),
        'hora_fin' => $horaFin->format('H:i:s'),
        'ahora' => $now->format('H:i:s'),
        'dentro_rango' => $dentroRango,
      ];

      if (!$dentroRango) {
        continue;
      }

      $candidatosIngreso->push([
        'horario' => $horario,
        'ventana_desde' => $ventanaDesdeIngreso,
        'hora_inicio' => $horaInicio,
        'hora_fin' => $horaFin,
      ]);
    }

    if ($candidatosIngreso->isNotEmpty()) {
      if ($cargoEspecial && $ingresoMismoDiaExiste) {
        $trace['resultado'] = 'rechazado_cargo_especial_ingreso_duplicado';

        return DecisionEventoDTO::rechazado('ya existe ingreso registrado hoy', $cargoId, true, $trace);
      }

      $seleccionadoIngreso = $candidatosIngreso
        ->sortByDesc(function ($item) {
          return $item['hora_inicio']->timestamp;
        })
        ->first();
      $horarioIngreso = $seleccionadoIngreso['horario'];
      $horaInicioIngreso = $seleccionadoIngreso['hora_inicio'];
      $umbralLlegadaTarde = $horaInicioIngreso->copy()->addMinutes(5);
      $llegadaTarde = $now->greaterThanOrEqualTo($umbralLlegadaTarde);
      $descripcion = $llegadaTarde ? 'LLEGADA TARDE' : 'entrada personal huellero';
      $trace['resultado'] = 'ok_ingreso_en_rango_jornada';
      $trace['horario_seleccionado_id'] = (int) $horarioIngreso->id;
      $trace['llegada_tarde'] = $llegadaTarde;
      $trace['umbral_llegada_tarde'] = $umbralLlegadaTarde->format('H:i:s');

      return DecisionEventoDTO::ok(
        self::EVENTO_INGRESO,
        (int) $horarioIngreso->id,
        $cargoId,
        $llegadaTarde,
        $cargoEspecial,
        $descripcion,
        $trace
      );
    }

    if ($cargoEspecial && $this->estaEnBloqueDeSalida($horarios, $now)) {
      if (!$ingresoHoyExiste) {
        $trace['resultado'] = 'rechazado_cargo_especial_sin_ingreso_en_bloque_salida';
        $trace['detalle_rechazo'] = 'Cargo especial: en bloque de salida pero no hay ingreso registrado hoy.';

        return DecisionEventoDTO::rechazado('no puede salir sin haber ingresado hoy', $cargoId, true, $trace);
      }

      if ($salidaMismoDiaExiste) {
        $trace['resultado'] = 'rechazado_cargo_especial_salida_duplicada';
        $trace['detalle_rechazo'] = 'Cargo especial: ya existe una salida registrada hoy.';

        return DecisionEventoDTO::rechazado('ya existe salida registrada hoy', $cargoId, true, $trace);
      }
    }

    $trace['resultado'] = 'rechazado_fuera_de_horarios';
    $trace['detalle_rechazo'] = 'Sin ingreso abierto para salida y fuera del rango hora_inicio-15..hora_fin para ingreso.';

    return DecisionEventoDTO::rechazado('fuera de horarios', $cargoId, $cargoEspecial, $trace);
  }

  public function resolverHorarioParaSalidaPorNovedad(
    string $identificacion,
    int $cargoId,
    Carbon $now
  ): array {
    $horarios = $this->obtenerHorariosAplicables($cargoId, $now);
    if ($horarios->isEmpty()) {
      return [
        'ok' => false,
        'motivo' => 'sin_horarios',
      ];
    }

    $eventosHoy = $this->obtenerEventosParaDecision($identificacion, $horarios);
    [$horariosConIngresoAbierto] = $this->resolverHorariosConIngresoAbierto($horarios, $eventosHoy, $now);

    if ($horariosConIngresoAbierto->isEmpty()) {
      return [
        'ok' => false,
        'motivo' => 'sin_ingreso_abierto',
      ];
    }

    $seleccionado = $horariosConIngresoAbierto
      ->map(function ($horario) use ($eventosHoy) {
        $horarioId = (int) ($horario->id ?? 0);
        $ultimoIngreso = $eventosHoy
          ->where('horario_cargo_id', $horarioId)
          ->where('evento', self::EVENTO_INGRESO)
          ->sortByDesc(function ($evento) {
            return $this->timestampEvento($evento);
          })
          ->first();

        return [
          'horario' => $horario,
          'ultimo_ingreso' => $this->fechaEvento($ultimoIngreso),
        ];
      })
      ->sortByDesc(function ($item) {
        return $item['ultimo_ingreso'] ? $item['ultimo_ingreso']->timestamp : 0;
      })
      ->first();

    if (!$seleccionado || !isset($seleccionado['horario'])) {
      return [
        'ok' => false,
        'motivo' => 'sin_horario_para_salida',
      ];
    }

    return [
      'ok' => true,
      'horario_cargo_id' => (int) $seleccionado['horario']->id,
      'ultimo_ingreso' => $seleccionado['ultimo_ingreso']
        ? $seleccionado['ultimo_ingreso']->format('Y-m-d H:i:s')
        : null,
    ];
  }

  public function resolverHorarioParaIngresoPorNovedad(int $cargoId, Carbon $now): array
  {
    $horarios = $this->obtenerHorariosAplicables($cargoId, $now);
    if ($horarios->isEmpty()) {
      return [
        'ok' => false,
        'motivo' => 'sin_horarios',
      ];
    }

    $evaluaciones = $horarios
      ->map(function ($horario) use ($now) {
        $ventanaHorario = $this->resolverVentanaHorario($now, $horario);
        if (!$ventanaHorario) {
          return null;
        }

        $horaInicio = $ventanaHorario['hora_inicio'];
        $horaFin = $ventanaHorario['hora_fin'];
        $ventanaDesdeIngreso = $ventanaHorario['ventana_desde_ingreso'];
        $dentroRango = !$now->lt($ventanaDesdeIngreso) && !$now->gt($horaFin);

        return [
          'horario' => $horario,
          'hora_inicio' => $horaInicio,
          'hora_fin' => $horaFin,
          'ventana_desde_ingreso' => $ventanaDesdeIngreso,
          'dentro_rango' => $dentroRango,
        ];
      })
      ->filter()
      ->values();

    if ($evaluaciones->isEmpty()) {
      return [
        'ok' => false,
        'motivo' => 'sin_horarios_validos',
      ];
    }

    $seleccionadoEnRango = $evaluaciones
      ->filter(function ($item) {
        return $item['dentro_rango'] === true;
      })
      ->sortByDesc(function ($item) {
        return $item['hora_inicio']->timestamp;
      })
      ->first();

    if ($seleccionadoEnRango) {
      return [
        'ok' => true,
        'horario_cargo_id' => (int) $seleccionadoEnRango['horario']->id,
        'criterio' => 'dentro_rango_ingreso',
      ];
    }

    $candidatosConDelta = $evaluaciones
      ->map(function ($item) use ($now) {
        $deltaSegundos = $item['hora_inicio']->timestamp - $now->timestamp;
        $item['delta_segundos'] = $deltaSegundos;

        return $item;
      })
      ->values();

    $seleccionadoFuturo = $candidatosConDelta
      ->filter(function ($item) {
        return $item['delta_segundos'] >= 0;
      })
      ->sortBy(function ($item) {
        return $item['delta_segundos'];
      })
      ->first();

    if ($seleccionadoFuturo) {
      return [
        'ok' => true,
        'horario_cargo_id' => (int) $seleccionadoFuturo['horario']->id,
        'criterio' => 'hora_inicio_mas_cercana_hacia_adelante',
      ];
    }

    return [
      'ok' => false,
      'motivo' => 'sin_horario_para_ingreso',
    ];
  }

  private function obtenerHorariosAplicables(int $cargoId, Carbon $now): Collection
  {
    $horariosCargo = PrsHorariosCargos::query()
      ->where('cargo_id', $cargoId)
      ->where('estado', 1)
      ->orderBy('id')
      ->get();

    $base = $horariosCargo->isNotEmpty()
      ? $horariosCargo
      : PrsHorariosCargos::query()
        ->whereNull('cargo_id')
        ->where('estado', 1)
        ->orderBy('id')
        ->get();

    $diaHoy = $this->diaSemanaIso($now);

    return $base
      ->filter(function ($horario) use ($diaHoy) {
        $diaInicio = (int) ($horario->dia_inicio ?? 0);
        $diaFin = (int) ($horario->dia_fin ?? 0);

        if ($diaInicio <= 0 || $diaFin <= 0) {
          return false;
        }

        return $this->diaEnRango($diaHoy, $diaInicio, $diaFin);
      })
      ->values();
  }

  private function obtenerEventosHoy(string $identificacion): Collection
  {
    return PrsHuellaEventos::query()
      ->where('identificacion', $identificacion)
      ->whereIn('evento', [self::EVENTO_SALIDA, self::EVENTO_INGRESO])
      ->whereRaw('fecha_creacion >= TRUNC(SYSDATE) AND fecha_creacion < TRUNC(SYSDATE) + 1')
      ->orderBy('fecha_creacion')
      ->get(['id', 'evento', 'horario_cargo_id', 'fecha_creacion']);
  }

  private function obtenerEventosHoyMasAyer(string $identificacion): Collection
  {
    return PrsHuellaEventos::query()
      ->where('identificacion', $identificacion)
      ->whereIn('evento', [self::EVENTO_SALIDA, self::EVENTO_INGRESO])
      ->whereRaw('fecha_creacion >= TRUNC(SYSDATE) - 1 AND fecha_creacion < TRUNC(SYSDATE) + 1')
      ->orderBy('fecha_creacion')
      ->get(['id', 'evento', 'horario_cargo_id', 'fecha_creacion']);
  }

  private function obtenerEventosParaDecision(string $identificacion, Collection $horarios): Collection
  {
    return $this->tieneHorariosQueCruzanMedianoche($horarios)
      ? $this->obtenerEventosHoyMasAyer($identificacion)
      : $this->obtenerEventosHoy($identificacion);
  }

  private function resolverHorariosConIngresoAbierto(Collection $horarios, Collection $eventosHoy, Carbon $now): array
  {
    $horariosAbiertos = $horarios
      ->map(function ($horario) use ($eventosHoy, $now) {
        $horarioId = (int) $horario->id;
        $ingresos = $eventosHoy
          ->where('horario_cargo_id', $horarioId)
          ->where('evento', self::EVENTO_INGRESO)
          ->sortBy(function ($evento) {
            return $this->timestampEvento($evento);
          })
          ->values();
        $salidas = $eventosHoy
          ->where('horario_cargo_id', $horarioId)
          ->where('evento', self::EVENTO_SALIDA)
          ->count();

        if ($ingresos->count() <= $salidas) {
          return null;
        }

        $ultimoIngreso = $ingresos->last();
        $fechaUltimoIngreso = $this->fechaEvento($ultimoIngreso);
        $ingresoPosterior = null;
        if ($fechaUltimoIngreso) {
          $ingresoPosterior = $eventosHoy
            ->where('evento', self::EVENTO_INGRESO)
            ->filter(function ($evento) use ($horarioId, $fechaUltimoIngreso) {
              $otroHorarioId = (int) ($evento->horario_cargo_id ?? 0);
              $fechaEvento = $this->fechaEvento($evento);

              return $otroHorarioId !== 0
                && $otroHorarioId !== $horarioId
                && $fechaEvento
                && $fechaEvento->gt($fechaUltimoIngreso);
            })
            ->sortByDesc(function ($evento) {
              return $this->timestampEvento($evento);
            })
            ->first();
        }

        $obsoletoPorCiclo = false;
        if ($fechaUltimoIngreso) {
          $ventanaHorario = $this->resolverVentanaHorario($now, $horario);
          if ($ventanaHorario) {
            $ventanaDesdeIngreso = $ventanaHorario['ventana_desde_ingreso'] ?? null;
            $horaFinVentana = $ventanaHorario['hora_fin'] ?? null;
            $enVentanaActual = $ventanaDesdeIngreso
              && $horaFinVentana
              && !$now->lt($ventanaDesdeIngreso)
              && !$now->gt($horaFinVentana);

            // Si ya inicio una nueva ventana del mismo horario y el ultimo ingreso
            // pertenece a un ciclo anterior, no debe bloquear un nuevo ingreso.
            $obsoletoPorCiclo = $enVentanaActual && $fechaUltimoIngreso->lt($ventanaDesdeIngreso);
          }
        }

        return [
          'horario' => $horario,
          'ultimo_ingreso' => $fechaUltimoIngreso,
          'ingreso_posterior' => $ingresoPosterior ? $this->fechaEvento($ingresoPosterior) : null,
          'horario_posterior_id' => $ingresoPosterior ? (int) ($ingresoPosterior->horario_cargo_id ?? 0) : null,
          'obsoleto_por_ciclo' => $obsoletoPorCiclo,
          'obsoleto' => $ingresoPosterior !== null || $obsoletoPorCiclo,
        ];
      })
      ->filter()
      ->sortBy(function ($item) {
        return (int) $item['horario']->id;
      })
      ->values();

    return [
      $horariosAbiertos
        ->filter(function ($item) {
          return $item['obsoleto'] === false;
        })
        ->map(function ($item) {
          return $item['horario'];
        })
        ->values(),
      $horariosAbiertos
        ->filter(function ($item) {
          return $item['obsoleto'] === true;
        })
        ->values(),
    ];
  }

  private function aplicaBloqueoSalidaPorOtraJornada(int $cargoId): bool
  {
    return !in_array($cargoId, $this->obtenerCargosEspecialesIds(), true);
  }

  private function resolverJornadasActivas(Collection $horarios, Carbon $now): Collection
  {
    return $horarios
      ->map(function ($horario) use ($now) {
        $ventanaHorario = $this->resolverVentanaHorario($now, $horario);
        if (!$ventanaHorario) {
          return null;
        }
        $horaInicio = $ventanaHorario['hora_inicio'];
        $horaFin = $ventanaHorario['hora_fin'];

        $ventanaDesdeIngreso = $ventanaHorario['ventana_desde_ingreso'];
        $dentroRango = !$now->lt($ventanaDesdeIngreso) && !$now->gt($horaFin);
        if (!$dentroRango) {
          return null;
        }

        return [
          'horario' => $horario,
          'ventana_desde' => $ventanaDesdeIngreso,
          'hora_inicio' => $horaInicio,
          'hora_fin' => $horaFin,
        ];
      })
      ->filter()
      ->values();
  }

  private function resolverBloqueoReingresoPostSalida(
    int $cargoId,
    Collection $eventosHoy,
    Carbon $now
  ): array
  {
    if (!in_array($cargoId, $this->obtenerCargosEspecialesIds(), true)) {
      return [
        'aplica' => false,
        'activo' => false,
      ];
    }

    $ultimaSalida = $eventosHoy
      ->where('evento', self::EVENTO_SALIDA)
      ->sortByDesc(function ($evento) {
        return $this->timestampEvento($evento);
      })
      ->first();

    if (!$ultimaSalida) {
      return [
        'aplica' => true,
        'activo' => false,
        'ultima_salida' => null,
      ];
    }

    $fechaSalida = $this->fechaEvento($ultimaSalida);
    if (!$fechaSalida) {
      return [
        'aplica' => true,
        'activo' => false,
        'ultima_salida' => null,
      ];
    }

    $bloqueaHasta = $fechaSalida->copy()->addHours($this->obtenerHorasBloqueoReingresoPostSalida());
    $activo = $now->lt($bloqueaHasta);

    return [
      'aplica' => true,
      'activo' => $activo,
      'ultima_salida' => $fechaSalida->format('Y-m-d H:i:s'),
      'bloquea_hasta' => $bloqueaHasta->format('Y-m-d H:i:s'),
      'minutos_restantes' => $activo ? max(0, $now->diffInMinutes($bloqueaHasta, false)) : 0,
    ];
  }

  private function obtenerCargosEspecialesIds(): array
  {
    if ($this->cargosEspecialesCache !== null) {
      return $this->cargosEspecialesCache;
    }

    return $this->cargosEspecialesCache = PrsHorariosCargos::query()
      ->whereNotNull('cargo_id')
      ->pluck('cargo_id')
      ->map(function ($cargoId) {
        return (int) $cargoId;
      })
      ->filter(function (int $cargoId) {
        return $cargoId > 0;
      })
      ->unique()
      ->values()
      ->all();
  }

  private function obtenerHorasBloqueoReingresoPostSalida(): int
  {
    $horas = (int) env('ASISTENCIA_HORAS_BLOQUEO_REINGRESO_POST_SALIDA', self::HORAS_BLOQUEO_REINGRESO_POST_SALIDA_DEFAULT);

    return $horas > 0 ? $horas : self::HORAS_BLOQUEO_REINGRESO_POST_SALIDA_DEFAULT;
  }

  private function timestampEvento(mixed $evento): int
  {
    $fecha = $this->fechaEvento($evento);

    return $fecha ? $fecha->timestamp : 0;
  }

  private function fechaEvento(mixed $evento): ?Carbon
  {
    if (!isset($evento->fecha_creacion) || $evento->fecha_creacion === null) {
      return null;
    }

    try {
      return Carbon::parse($evento->fecha_creacion);
    } catch (\Throwable $e) {
      return null;
    }
  }

  private function existeEventoMismoDiaOIndeterminado(Collection $eventos, int $tipoEvento, Carbon $now): bool
  {
    return $eventos->contains(function ($evento) use ($tipoEvento, $now) {
      if ((int) ($evento->evento ?? 0) !== $tipoEvento) {
        return false;
      }

      $fecha = $this->fechaEvento($evento);
      if (!$fecha) {
        return true;
      }

      return $fecha->isSameDay($now);
    });
  }

  private function estaEnBloqueDeSalida(Collection $horarios, Carbon $now): bool
  {
    return $horarios->contains(function ($horario) use ($now) {
      $ventanaHorario = $this->resolverVentanaHorario($now, $horario);
      if (!$ventanaHorario) {
        return false;
      }
      $horaFin = $ventanaHorario['hora_fin'];

      return $horaFin && $now->greaterThanOrEqualTo($horaFin);
    });
  }

  private function resolverVentanaHorario(Carbon $now, object $horario): ?array
  {
    $horaInicio = $this->horaSolo($horario->hora_inicio ?? null);
    $horaFin = $this->horaSolo($horario->hora_fin ?? null);
    if (!$horaInicio || !$horaFin) {
      return null;
    }

    $inicioMinutos = ((int) $horaInicio->format('H') * 60) + (int) $horaInicio->format('i');
    $finMinutos = ((int) $horaFin->format('H') * 60) + (int) $horaFin->format('i');
    $cruzaMedianoche = $inicioMinutos > $finMinutos;

    if (!$cruzaMedianoche) {
      $inicio = $this->combinarFechaYHora($now, $horaInicio);
      $fin = $this->combinarFechaYHora($now, $horaFin);
      if (!$inicio || !$fin) {
        return null;
      }

      return [
        'hora_inicio' => $inicio,
        'hora_fin' => $fin,
        'ventana_desde_ingreso' => $inicio->copy()->subMinutes(15),
      ];
    }

    $inicioAyer = $this->combinarFechaYHora($now->copy()->subDay(), $horaInicio);
    $finHoy = $this->combinarFechaYHora($now, $horaFin);
    $inicioHoy = $this->combinarFechaYHora($now, $horaInicio);
    $finManana = $this->combinarFechaYHora($now->copy()->addDay(), $horaFin);
    if (!$inicioAyer || !$finHoy || !$inicioHoy || !$finManana) {
      return null;
    }

    $candidatos = collect([
      [
        'hora_inicio' => $inicioAyer,
        'hora_fin' => $finHoy,
      ],
      [
        'hora_inicio' => $inicioHoy,
        'hora_fin' => $finManana,
      ],
    ])->map(function ($item) {
      $item['ventana_desde_ingreso'] = $item['hora_inicio']->copy()->subMinutes(15);

      return $item;
    });

    $contieneAhora = $candidatos
      ->filter(function ($item) use ($now) {
        return !$now->lt($item['ventana_desde_ingreso']) && !$now->gt($item['hora_fin']);
      })
      ->sortByDesc(function ($item) {
        return $item['hora_inicio']->timestamp;
      })
      ->first();
    if ($contieneAhora) {
      return $contieneAhora;
    }

    $pasadoMasReciente = $candidatos
      ->filter(function ($item) use ($now) {
        return $item['hora_fin']->lessThanOrEqualTo($now);
      })
      ->sortByDesc(function ($item) {
        return $item['hora_fin']->timestamp;
      })
      ->first();
    if ($pasadoMasReciente) {
      return $pasadoMasReciente;
    }

    return $candidatos
      ->sortBy(function ($item) {
        return $item['hora_fin']->timestamp;
      })
      ->first();
  }

  private function tieneHorariosQueCruzanMedianoche(Collection $horarios): bool
  {
    return $horarios->contains(function ($horario) {
      return $this->horarioCruzaMedianoche($horario);
    });
  }

  private function horarioCruzaMedianoche(object $horario): bool
  {
    $horaInicio = $this->horaSolo($horario->hora_inicio ?? null);
    $horaFin = $this->horaSolo($horario->hora_fin ?? null);
    if (!$horaInicio || !$horaFin) {
      return false;
    }

    $inicioMinutos = ((int) $horaInicio->format('H') * 60) + (int) $horaInicio->format('i');
    $finMinutos = ((int) $horaFin->format('H') * 60) + (int) $horaFin->format('i');

    return $inicioMinutos > $finMinutos;
  }

  private function horaSolo(mixed $horaOracle): ?Carbon
  {
    if (!$horaOracle) {
      return null;
    }

    try {
      $hora = $horaOracle instanceof Carbon
        ? $horaOracle->copy()
        : Carbon::parse((string) $horaOracle);
    } catch (\Throwable $e) {
      return null;
    }

    return Carbon::createFromTime(
      (int) $hora->format('H'),
      (int) $hora->format('i'),
      (int) $hora->format('s')
    );
  }

  private function combinarFechaYHora(Carbon $fecha, mixed $horaOracle): ?Carbon
  {
    if (!$horaOracle) {
      return null;
    }

    $hora = $horaOracle instanceof Carbon
      ? $horaOracle->copy()
      : Carbon::parse((string) $horaOracle);

    return $fecha->copy()->setTime(
      (int) $hora->format('H'),
      (int) $hora->format('i'),
      (int) $hora->format('s')
    );
  }

  private function diaSemanaIso(Carbon $date): int
  {
    return (int) $date->copy()->dayOfWeekIso;
  }

  private function diaEnRango(int $dia, int $inicio, int $fin): bool
  {
    if ($inicio <= $fin) {
      return $dia >= $inicio && $dia <= $fin;
    }

    return $dia >= $inicio || $dia <= $fin;
  }

  private function mapHorarios(Collection $horarios): array
  {
    return $horarios->map(function ($horario) {
      return [
        'id' => (int) ($horario->id ?? 0),
        'cargo_id' => isset($horario->cargo_id) ? (int) $horario->cargo_id : null,
        'dia_inicio' => isset($horario->dia_inicio) ? (int) $horario->dia_inicio : null,
        'dia_fin' => isset($horario->dia_fin) ? (int) $horario->dia_fin : null,
        'hora_inicio' => $this->horaTexto($horario->hora_inicio ?? null),
        'hora_fin' => $this->horaTexto($horario->hora_fin ?? null),
      ];
    })->values()->all();
  }

  private function mapEventos(Collection $eventosHoy): array
  {
    return $eventosHoy->map(function ($evento) {
      $fechaCreacion = null;
      if (isset($evento->fecha_creacion) && $evento->fecha_creacion !== null) {
        try {
          $fechaCreacion = Carbon::parse($evento->fecha_creacion)->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
          $fechaCreacion = (string) $evento->fecha_creacion;
        }
      }

      return [
        'evento' => (int) ($evento->evento ?? 0),
        'horario_cargo_id' => isset($evento->horario_cargo_id) ? (int) $evento->horario_cargo_id : null,
        'fecha_creacion' => $fechaCreacion,
      ];
    })->values()->all();
  }

  private function horaTexto(mixed $hora): ?string
  {
    if (!$hora) {
      return null;
    }

    return Carbon::parse((string) $hora)->format('H:i:s');
  }

  /*
   * Decision log:
   * - Mapeo de dia: se usa dayOfWeekIso de Carbon (1=Lunes ... 7=Domingo).
   * - Hora Oracle DATE: se ignora su fecha y se combina HH:mm:ss con la fecha de $now.
   * - Jornada/evento:
   *   1) Si hay ingreso abierto hoy por mismo HORARIO_CARGO_ID, decide SALIDA para ese ID solo si cumple HORA_FIN.
   *      Si una jornada abierta tiene un ingreso posterior en otra jornada, la anterior queda obsoleta y ya no puede cerrar con una salida tardia.
   *      Para cargos por defecto, si now cae en otra jornada valida, prioriza INGRESO para esa nueva jornada.
   *      Si no existe candidato de transicion, bloquea la salida.
   *      Los cargos de la lista especial mantienen su flujo actual.
   *   2) Si no hay ingreso abierto, permite INGRESO cuando now esta dentro de HORA_INICIO-15..HORA_FIN.
   *   3) Si ninguna regla aplica, rechaza por fuera de horarios.
   */
}
