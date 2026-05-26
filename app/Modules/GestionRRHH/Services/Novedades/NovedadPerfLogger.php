<?php

namespace App\Modules\GestionRRHH\Services\Novedades;

use Illuminate\Support\Facades\Log;

class NovedadPerfLogger
{
    public static function start(string $flujo, array $contexto = []): array
    {
        if (! self::enabled()) {
            return [];
        }

        $now = hrtime(true);

        return [
            'flujo' => $flujo,
            'inicio' => $now,
            'ultimo' => $now,
            'pasos' => [],
            'contexto' => $contexto,
        ];
    }

    public static function checkpoint(array &$traza, string $paso, array $contexto = []): void
    {
        if ($traza === []) {
            return;
        }

        $now = hrtime(true);
        $deltaMs = round(($now - (int) $traza['ultimo']) / 1_000_000, 2);
        $acumuladoMs = round(($now - (int) $traza['inicio']) / 1_000_000, 2);
        $traza['ultimo'] = $now;
        $traza['pasos'][] = [
            'paso' => $paso,
            'delta_ms' => $deltaMs,
            'acumulado_ms' => $acumuladoMs,
            'contexto' => $contexto,
        ];
    }

    public static function finish(array $traza, array $contextoFinal = []): void
    {
        if ($traza === []) {
            return;
        }

        $now = hrtime(true);
        $totalMs = round(($now - (int) $traza['inicio']) / 1_000_000, 2);
        $umbralMs = (int) config('services.employee_permits.performance.threshold_ms', 0);

        if ($totalMs < $umbralMs) {
            return;
        }

        $level = strtolower(trim((string) config('services.employee_permits.performance.log_level', 'info')));
        if (! in_array($level, ['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'], true)) {
            $level = 'info';
        }

        Log::log($level, 'RRHH_PERF_RADICACION', [
            'flujo' => (string) ($traza['flujo'] ?? ''),
            'total_ms' => $totalMs,
            'pasos' => (array) ($traza['pasos'] ?? []),
            'contexto' => array_merge((array) ($traza['contexto'] ?? []), $contextoFinal),
        ]);
    }

    private static function enabled(): bool
    {
        return (bool) config('services.employee_permits.performance.enabled', false);
    }
}
