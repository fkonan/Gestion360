<?php

namespace App\Modules\Administration\Services\Reportes;

use App\Modules\Administration\Models\Reporteador;
use Carbon\Carbon;
use InvalidArgumentException;

class ReporteRangoFechasService
{
    public function obtenerLimiteMeses(Reporteador $reporte): ?int
    {
        if ($reporte->max_meses_consulta !== null) {
            $limite = (int) $reporte->max_meses_consulta;

            return $limite > 0 ? $limite : null;
        }

        $sinLimite = array_map('intval', (array) config(
            'reporteador.reportes_rango_fechas.sin_limite',
            []
        ));

        if (in_array((int) $reporte->id, $sinLimite, true)) {
            return null;
        }

        $maxMesesPorReporte = (array) config(
            'reporteador.reportes_rango_fechas.max_meses_por_reporte',
            []
        );

        if (array_key_exists($reporte->id, $maxMesesPorReporte)) {
            return max(1, (int) $maxMesesPorReporte[$reporte->id]);
        }

        return max(1, (int) config('reporteador.reportes_rango_fechas.default_meses', 1));
    }

    public function construirMensajeCabecera(
        bool $tieneFechaInicio,
        bool $tieneFechaFin,
        bool $hayParametros,
        Reporteador $reporte
    ): string {
        if (! $tieneFechaInicio && ! $tieneFechaFin) {
            return $hayParametros
                ? 'Este reporte no requiere de un rango de fechas.'
                : 'Este reporte no requiere parametros.';
        }

        $limiteMeses = $this->obtenerLimiteMeses($reporte);

        if ($limiteMeses === null) {
            return 'Este reporte no tiene restriccion maxima en el rango de fechas.';
        }

        return 'El rango de fechas no puede ser mayor a '.$this->formatearLimiteMeses($limiteMeses).'.';
    }

    public function normalizarFecha(?string $valor): ?Carbon
    {
        $valor = trim((string) $valor);

        if ($valor === '') {
            return null;
        }

        return Carbon::parse($valor);
    }

    public function validarRango(Reporteador $reporte, ?Carbon $fechaInicio, ?Carbon $fechaFin): void
    {
        if (! $fechaInicio || ! $fechaFin) {
            return;
        }

        $limiteMeses = $this->obtenerLimiteMeses($reporte);

        if ($limiteMeses === null) {
            return;
        }

        $fechaMaxima = $fechaInicio->copy()->addMonthsNoOverflow($limiteMeses);

        if ($fechaInicio->diffInMonths($fechaFin) > $limiteMeses || $fechaFin->gt($fechaMaxima)) {
            throw new InvalidArgumentException(
                'El rango entre las fechas no puede ser mayor a '.$this->formatearLimiteMeses($limiteMeses).'.'
            );
        }
    }

    private function formatearLimiteMeses(int $limiteMeses): string
    {
        return $limiteMeses === 1 ? '1 mes' : $limiteMeses.' meses';
    }
}
