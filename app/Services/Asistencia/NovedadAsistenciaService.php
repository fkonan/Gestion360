<?php

namespace App\Services\Asistencia;

use App\Modules\Huellero\Models\EmpNovedad;
use Carbon\Carbon;

class NovedadAsistenciaService
{
  private const MINUTOS_ANTES_DEFAULT = 5;
  private const MINUTOS_DESPUES_DEFAULT = 20;

  /**
   * Busca novedad aprobada en ventana y define evento permitido:
   * - Fecha inicio: salida (1)
   * - Fecha fin: ingreso (2)
   */
  public function resolverEventoPermitido(string $identificacion, Carbon $fecha): array
  {
    $identificacion = trim($identificacion);
    if ($identificacion === '') {
      return [
        'aplica' => false,
      ];
    }

    $minAntes = $this->obtenerMinutosAntes();
    $minDespues = $this->obtenerMinutosDespues();
    $fechaDesde = $fecha->copy()->subMinutes($minDespues);
    $fechaHasta = $fecha->copy()->addMinutes($minAntes);

    $novedades = EmpNovedad::query()
      ->from('EMP_NOVEDADES as n')
      ->leftJoin('EMP_NOVEDADES_TIPO as t', 't.id', '=', 'n.id_tipo_novedad')
      ->where('n.id_persona', $identificacion)
      ->where('n.estado', 'APROBADO')
      ->where(function ($query) use ($fechaDesde, $fechaHasta) {
        $query
          ->whereBetween('n.fecha_inicio', [$fechaDesde, $fechaHasta])
          ->orWhere(function ($subQuery) use ($fechaDesde, $fechaHasta) {
            $subQuery
              ->whereNotNull('n.fecha_fin')
              ->whereBetween('n.fecha_fin', [$fechaDesde, $fechaHasta]);
          });
      })
      ->select([
        'n.id',
        'n.id_persona',
        'n.id_tipo_novedad',
        'n.fecha_inicio',
        'n.fecha_fin',
        'n.estado',
        'n.observacion',
        't.descripcion as tipo_descripcion',
      ])
      ->get();

    if ($novedades->isEmpty()) {
      return [
        'aplica' => false,
      ];
    }

    $novedad = $novedades
      ->map(function ($item) use ($fecha, $minAntes, $minDespues) {
        $fechaInicio = $this->parseFecha($item->fecha_inicio ?? null);
        $fechaFin = $this->parseFecha($item->fecha_fin ?? null);
        $coincideInicio = $fechaInicio
          ? $fecha->betweenIncluded($fechaInicio->copy()->subMinutes($minAntes), $fechaInicio->copy()->addMinutes($minDespues))
          : false;
        $coincideFin = $fechaFin
          ? $fecha->betweenIncluded($fechaFin->copy()->subMinutes($minAntes), $fechaFin->copy()->addMinutes($minDespues))
          : false;
        $eventoResuelto = $this->resolverEventoPorCoincidencia(
          $fecha,
          $fechaInicio,
          $fechaFin,
          $coincideInicio,
          $coincideFin
        );
        if ($eventoResuelto === null) {
          return null;
        }

        return [
          'row' => $item,
          'coincide_inicio' => $coincideInicio,
          'coincide_fin' => $coincideFin,
          'fecha_inicio' => $fechaInicio,
          'fecha_fin' => $fechaFin,
          'evento' => $eventoResuelto['evento'],
          'ventana' => $eventoResuelto['ventana'],
          'fecha_referencia' => $eventoResuelto['fecha_referencia'],
          'distancia_referencia_segundos' => $eventoResuelto['distancia_referencia_segundos'],
        ];
      })
      ->filter(function ($item) {
        return $item !== null && ($item['coincide_inicio'] || $item['coincide_fin']);
      })
      ->sort(function ($a, $b) {
        $distanciaA = (int) ($a['distancia_referencia_segundos'] ?? PHP_INT_MAX);
        $distanciaB = (int) ($b['distancia_referencia_segundos'] ?? PHP_INT_MAX);
        if ($distanciaA !== $distanciaB) {
          return $distanciaA <=> $distanciaB;
        }

        $referenciaA = $a['fecha_referencia'] instanceof Carbon ? $a['fecha_referencia']->timestamp : 0;
        $referenciaB = $b['fecha_referencia'] instanceof Carbon ? $b['fecha_referencia']->timestamp : 0;
        if ($referenciaA !== $referenciaB) {
          return $referenciaB <=> $referenciaA;
        }

        $aInicio = $a['fecha_inicio'] ? $a['fecha_inicio']->timestamp : 0;
        $bInicio = $b['fecha_inicio'] ? $b['fecha_inicio']->timestamp : 0;
        if ($aInicio !== $bInicio) {
          return $bInicio <=> $aInicio;
        }

        $aFin = $a['fecha_fin'] ? $a['fecha_fin']->timestamp : 0;
        $bFin = $b['fecha_fin'] ? $b['fecha_fin']->timestamp : 0;

        return $bFin <=> $aFin;
      })
      ->first();

    if (!$novedad) {
      return [
        'aplica' => false,
      ];
    }

    $row = $novedad['row'];
    $coincideInicio = (bool) $novedad['coincide_inicio'];
    $coincideFin = (bool) $novedad['coincide_fin'];

    if (!$coincideInicio && !$coincideFin) {
      return [
        'aplica' => false,
      ];
    }

    $evento = (int) ($novedad['evento'] ?? 0);
    $ventana = (string) ($novedad['ventana'] ?? '');
    $fechaReferencia = $novedad['fecha_referencia'] ?? null;
    if (!in_array($evento, [1, 2], true) || !in_array($ventana, ['fecha_inicio', 'fecha_fin'], true)) {
      return [
        'aplica' => false,
      ];
    }

    return [
      'aplica' => true,
      'evento' => $evento,
      'ventana' => $ventana,
      'novedad_id' => (string) ($row->id ?? ''),
      'id_persona' => (string) ($row->id_persona ?? ''),
      'tipo_novedad_id' => (string) ($row->id_tipo_novedad ?? ''),
      'tipo_novedad' => $row->tipo_descripcion ?? null,
      'fecha_inicio' => $this->formatearFecha($row->fecha_inicio ?? null),
      'fecha_fin' => $this->formatearFecha($row->fecha_fin ?? null),
      'fecha_referencia' => $this->formatearFecha($fechaReferencia),
      'observacion' => $row->observacion ?? null,
      'minutos_antes' => $minAntes,
      'minutos_despues' => $minDespues,
      'coincide_inicio' => $coincideInicio,
      'coincide_fin' => $coincideFin,
      'distancia_referencia_segundos' => (int) ($novedad['distancia_referencia_segundos'] ?? 0),
    ];
  }

  private function obtenerMinutosAntes(): int
  {
    $valor = (int) env('ASISTENCIA_NOVEDAD_MINUTOS_ANTES', self::MINUTOS_ANTES_DEFAULT);

    return $valor >= 0 ? $valor : self::MINUTOS_ANTES_DEFAULT;
  }

  private function obtenerMinutosDespues(): int
  {
    $valor = (int) env('ASISTENCIA_NOVEDAD_MINUTOS_DESPUES', self::MINUTOS_DESPUES_DEFAULT);

    return $valor >= 0 ? $valor : self::MINUTOS_DESPUES_DEFAULT;
  }

  private function formatearFecha(mixed $fecha): ?string
  {
    $valor = $this->parseFecha($fecha);
    if (!$valor) {
      return null;
    }

    return $valor->format('Y-m-d H:i:s');
  }

  private function parseFecha(mixed $fecha): ?Carbon
  {
    if (!$fecha) {
      return null;
    }

    try {
      return Carbon::parse($fecha);
    } catch (\Throwable $e) {
      return null;
    }
  }

  private function resolverEventoPorCoincidencia(
    Carbon $fecha,
    ?Carbon $fechaInicio,
    ?Carbon $fechaFin,
    bool $coincideInicio,
    bool $coincideFin
  ): ?array {
    if (!$coincideInicio && !$coincideFin) {
      return null;
    }

    if ($coincideInicio && !$coincideFin) {
      return [
        'evento' => 1,
        'ventana' => 'fecha_inicio',
        'fecha_referencia' => $fechaInicio,
        'distancia_referencia_segundos' => $this->distanciaSegundos($fecha, $fechaInicio),
      ];
    }

    if ($coincideFin && !$coincideInicio) {
      return [
        'evento' => 2,
        'ventana' => 'fecha_fin',
        'fecha_referencia' => $fechaFin,
        'distancia_referencia_segundos' => $this->distanciaSegundos($fecha, $fechaFin),
      ];
    }

    $distInicio = $this->distanciaSegundos($fecha, $fechaInicio);
    $distFin = $this->distanciaSegundos($fecha, $fechaFin);

    if ($distFin < $distInicio) {
      return [
        'evento' => 2,
        'ventana' => 'fecha_fin',
        'fecha_referencia' => $fechaFin,
        'distancia_referencia_segundos' => $distFin,
      ];
    }

    return [
      'evento' => 1,
      'ventana' => 'fecha_inicio',
      'fecha_referencia' => $fechaInicio,
      'distancia_referencia_segundos' => $distInicio,
    ];
  }

  private function distanciaSegundos(Carbon $fecha, ?Carbon $referencia): int
  {
    if (!$referencia) {
      return PHP_INT_MAX;
    }

    return abs($fecha->timestamp - $referencia->timestamp);
  }
}
