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
  private const CARGO_ESPECIAL_ID = 8;
  private const CARGOS_BLOQUEO_REINGRESO_POST_SALIDA = [8];
  private const HORAS_BLOQUEO_REINGRESO_POST_SALIDA = 7;

  public function decidir(string $identificacion, int $cargoId, Carbon $now): DecisionEventoDTO
  {
    $horarios = $this->obtenerHorariosAplicables($cargoId, $now);
    $eventosHoy = $this->obtenerEventosHoy($identificacion);

    return $this->decidirConDatos($cargoId, $now, $horarios, $eventosHoy);
  }

  public function decidirConDatos(
    int $cargoId,
    Carbon $now,
    Collection $horarios,
    Collection $eventosHoy
  ): DecisionEventoDTO {
    $cargoEspecial = $cargoId === self::CARGO_ESPECIAL_ID;
    $trace = [
      'dia_semana' => $this->diaSemanaIso($now),
      'fecha_hora' => $now->format('Y-m-d H:i:s'),
      'criterios' => [
        'ingreso_rango_jornada' => 'hora_inicio -15 min <= ahora <= hora_fin cuando no hay ingreso abierto',
        'salida_requiere_ingreso_abierto' => true,
        'salida_desde_hora_fin' => true,
        'salida_fuera_de_otra_jornada' => 'para cargos por defecto, no permite salida si ahora cae en otra jornada valida',
        'bloqueo_reingreso_post_salida_cargos' => self::CARGOS_BLOQUEO_REINGRESO_POST_SALIDA,
        'bloqueo_reingreso_post_salida_horas' => self::HORAS_BLOQUEO_REINGRESO_POST_SALIDA,
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
    $trace['ingreso_hoy_existe'] = $ingresoHoyExiste;
    $trace['salida_hoy_existe'] = $salidaHoyExiste;

    $horariosConIngresoAbierto = $this->resolverHorariosConIngresoAbierto($horarios, $eventosHoy);
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
          $horaFin = $this->combinarFechaYHora($now, $horario->hora_fin);
          if (!$horaFin) {
            return null;
          }

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

          if ($salidaHoyExiste) {
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

    if ($cargoEspecial && $ingresoHoyExiste) {
      $trace['resultado'] = 'rechazado_cargo_especial_ingreso_duplicado';
      $trace['detalle_rechazo'] = 'Cargo especial: ya existe un ingreso registrado hoy.';

      return DecisionEventoDTO::rechazado('ya existe ingreso registrado hoy', $cargoId, true, $trace);
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
      $horaInicio = $this->combinarFechaYHora($now, $horario->hora_inicio);
      $horaFin = $this->combinarFechaYHora($now, $horario->hora_fin);
      if (!$horaInicio || !$horaFin) {
        continue;
      }

      $ventanaDesdeIngreso = $horaInicio->copy()->subMinutes(15);
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
      if ($cargoEspecial && $ingresoHoyExiste) {
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

    if ($cargoEspecial && !$ingresoHoyExiste && $this->estaEnBloqueDeSalida($horarios, $now)) {
      $trace['resultado'] = 'rechazado_cargo_especial_sin_ingreso_en_bloque_salida';
      $trace['detalle_rechazo'] = 'Cargo especial: en bloque de salida pero no hay ingreso registrado hoy.';

      return DecisionEventoDTO::rechazado('no puede salir sin haber ingresado hoy', $cargoId, true, $trace);
    }

    $trace['resultado'] = 'rechazado_fuera_de_horarios';
    $trace['detalle_rechazo'] = 'Sin ingreso abierto para salida y fuera del rango hora_inicio-15..hora_fin para ingreso.';

    return DecisionEventoDTO::rechazado('fuera de horarios', $cargoId, $cargoEspecial, $trace);
  }

  private function obtenerHorariosAplicables(int $cargoId, Carbon $now): Collection
  {
    $horariosCargo = PrsHorariosCargos::query()
      ->where('cargo_id', $cargoId)
      ->orderBy('id')
      ->get();

    $base = $horariosCargo->isNotEmpty()
      ? $horariosCargo
      : PrsHorariosCargos::query()
        ->whereNull('cargo_id')
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

  private function resolverHorariosConIngresoAbierto(Collection $horarios, Collection $eventosHoy): Collection
  {
    return $horarios
      ->filter(function ($horario) use ($eventosHoy) {
        $horarioId = (int) $horario->id;
        $ingresos = $eventosHoy
          ->where('horario_cargo_id', $horarioId)
          ->where('evento', self::EVENTO_INGRESO)
          ->count();
        $salidas = $eventosHoy
          ->where('horario_cargo_id', $horarioId)
          ->where('evento', self::EVENTO_SALIDA)
          ->count();

        return $ingresos > $salidas;
      })
      ->sortBy('id')
      ->values();
  }

  private function aplicaBloqueoSalidaPorOtraJornada(int $cargoId): bool
  {
    return !in_array($cargoId, self::CARGOS_BLOQUEO_REINGRESO_POST_SALIDA, true);
  }

  private function resolverJornadasActivas(Collection $horarios, Carbon $now): Collection
  {
    return $horarios
      ->map(function ($horario) use ($now) {
        $horaInicio = $this->combinarFechaYHora($now, $horario->hora_inicio);
        $horaFin = $this->combinarFechaYHora($now, $horario->hora_fin);
        if (!$horaInicio || !$horaFin) {
          return null;
        }

        $ventanaDesdeIngreso = $horaInicio->copy()->subMinutes(15);
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
    if (!in_array($cargoId, self::CARGOS_BLOQUEO_REINGRESO_POST_SALIDA, true)) {
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

    $bloqueaHasta = $fechaSalida->copy()->addHours(self::HORAS_BLOQUEO_REINGRESO_POST_SALIDA);
    $activo = $now->lt($bloqueaHasta);

    return [
      'aplica' => true,
      'activo' => $activo,
      'ultima_salida' => $fechaSalida->format('Y-m-d H:i:s'),
      'bloquea_hasta' => $bloqueaHasta->format('Y-m-d H:i:s'),
      'minutos_restantes' => $activo ? max(0, $now->diffInMinutes($bloqueaHasta, false)) : 0,
    ];
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

  private function estaEnBloqueDeSalida(Collection $horarios, Carbon $now): bool
  {
    return $horarios->contains(function ($horario) use ($now) {
      $horaFin = $this->combinarFechaYHora($now, $horario->hora_fin);

      return $horaFin && $now->greaterThanOrEqualTo($horaFin);
    });
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
   *      Para cargos por defecto, si now cae en otra jornada valida, prioriza INGRESO para esa nueva jornada.
   *      Si no existe candidato de transicion, bloquea la salida.
   *      Los cargos de la lista especial mantienen su flujo actual.
   *   2) Si no hay ingreso abierto, permite INGRESO cuando now esta dentro de HORA_INICIO-15..HORA_FIN.
   *   3) Si ninguna regla aplica, rechaza por fuera de horarios.
   */
}
