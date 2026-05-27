<?php

namespace App\Modules\GestionRRHH\Services;

use App\Modules\GestionRRHH\Models\DesbloqueoFicsPendiente;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class DesbloqueoFicsPendienteService
{
    public const MAX_INTENTOS_DEFAULT = 24;

    public function registrarPendiente(
        string $identificacion,
        int $idBloqueoFics,
        bool $reactivarSiSinBloqueos = false,
        ?string $origen = null,
        ?string $error = null
    ): DesbloqueoFicsPendiente {
        $documento = $this->normalizarDocumento($identificacion);
        $idBloqueoFics = (int) $idBloqueoFics;

        $pendiente = DesbloqueoFicsPendiente::query()
            ->abiertos()
            ->where('identificacion', $documento)
            ->where('id_bloqueo_fics', $idBloqueoFics)
            ->orderByDesc('id')
            ->first();

        if ($pendiente) {
            $pendiente->fill([
                'reactivar_si_sin_bloqueos' => $reactivarSiSinBloqueos,
                'origen' => $this->limpiarTexto($origen, 60),
                'estado' => DesbloqueoFicsPendiente::ESTADO_PENDIENTE,
                'ultimo_error' => $this->limpiarTexto($error),
                'proximo_intento_at' => now(),
            ]);
            $pendiente->save();

            return $pendiente;
        }

        return DesbloqueoFicsPendiente::query()->create([
            'identificacion' => $documento,
            'id_bloqueo_fics' => $idBloqueoFics,
            'reactivar_si_sin_bloqueos' => $reactivarSiSinBloqueos,
            'origen' => $this->limpiarTexto($origen, 60),
            'estado' => DesbloqueoFicsPendiente::ESTADO_PENDIENTE,
            'intentos' => 0,
            'ultimo_error' => $this->limpiarTexto($error),
            'proximo_intento_at' => now(),
        ]);
    }

    /**
     * @return Collection<int, DesbloqueoFicsPendiente>
     */
    public function obtenerPendientesListos(int $limit = 200, ?string $identificacion = null, bool $forzar = false): Collection
    {
        $limit = max(1, min($limit, 2000));
        $documento = $this->normalizarDocumento((string) $identificacion);

        $query = DesbloqueoFicsPendiente::query()
            ->abiertos()
            ->orderBy('proximo_intento_at')
            ->orderBy('created_at');

        if (! $forzar) {
            $query->where(function ($subQuery) {
                $subQuery->whereNull('proximo_intento_at')
                    ->orWhere('proximo_intento_at', '<=', now());
            });
        }

        if ($documento !== '') {
            $query->where('identificacion', $documento);
        }

        return $query->limit($limit)->get();
    }

    public function reprocesarPendientes(
        int $limit = 200,
        ?string $identificacion = null,
        bool $forzar = false,
        bool $dryRun = false,
        int $maxIntentos = self::MAX_INTENTOS_DEFAULT
    ): array {
        $pendientes = $this->obtenerPendientesListos($limit, $identificacion, $forzar);

        $resultado = [
            'seleccionados' => $pendientes->count(),
            'procesados' => 0,
            'resueltos' => 0,
            'fallidos' => 0,
            'reprogramados' => 0,
            'omitidos' => 0,
            'errores' => [],
        ];

        foreach ($pendientes as $pendiente) {
            if ($dryRun) {
                $resultado['procesados']++;
                continue;
            }

            $capturado = DesbloqueoFicsPendiente::query()
                ->whereKey($pendiente->id)
                ->whereIn('estado', [
                    DesbloqueoFicsPendiente::ESTADO_PENDIENTE,
                    DesbloqueoFicsPendiente::ESTADO_FALLIDO,
                ])
                ->update([
                    'estado' => DesbloqueoFicsPendiente::ESTADO_PROCESANDO,
                    'ultimo_intento_at' => now(),
                    'intentos' => $pendiente->intentos + 1,
                    'updated_at' => now(),
                ]);

            if ($capturado !== 1) {
                $resultado['omitidos']++;
                continue;
            }

            $pendiente = DesbloqueoFicsPendiente::query()->find($pendiente->id);
            if (! $pendiente) {
                $resultado['omitidos']++;
                continue;
            }

            $resultado['procesados']++;

            try {
                $ok = BloqueoService::levantarBloqueoFICS(
                    $pendiente->identificacion,
                    (int) $pendiente->id_bloqueo_fics,
                    false
                );

                if ($ok) {
                    $pendiente->estado = DesbloqueoFicsPendiente::ESTADO_RESUELTO;
                    $pendiente->resuelto_at = now();
                    $pendiente->ultimo_error = null;
                    $pendiente->proximo_intento_at = null;
                    $pendiente->save();
                    $resultado['resueltos']++;

                    continue;
                }

                $error = 'No se encontro un bloqueo FICS activo para levantar o el desbloqueo fue rechazado.';
                $this->marcarIntentoFallido($pendiente, $error, $maxIntentos);
                $resultado['fallidos']++;
                $resultado['reprogramados']++;
                $resultado['errores'][] = $this->resumenError($pendiente, $error);
            } catch (\Throwable $e) {
                $error = $e->getMessage();
                $this->marcarIntentoFallido($pendiente, $error, $maxIntentos);
                $resultado['fallidos']++;
                $resultado['reprogramados']++;
                $resultado['errores'][] = $this->resumenError($pendiente, $error);

                Log::error('Error reprocesando desbloqueo FICS pendiente', [
                    'pendiente_id' => $pendiente->id,
                    'identificacion' => $pendiente->identificacion,
                    'id_bloqueo_fics' => $pendiente->id_bloqueo_fics,
                    'error' => $error,
                ]);
            }
        }

        return $resultado;
    }

    private function marcarIntentoFallido(DesbloqueoFicsPendiente $pendiente, string $error, int $maxIntentos): void
    {
        $intentos = (int) $pendiente->intentos;
        $intentosMaximos = max(1, $maxIntentos);
        $estado = $intentos >= $intentosMaximos
            ? DesbloqueoFicsPendiente::ESTADO_FALLIDO
            : DesbloqueoFicsPendiente::ESTADO_PENDIENTE;

        $pendiente->estado = $estado;
        $pendiente->ultimo_error = $this->limpiarTexto($error);
        $pendiente->proximo_intento_at = $this->calcularProximoIntento($intentos);
        $pendiente->save();
    }

    private function calcularProximoIntento(int $intentos): Carbon
    {
        $escala = min(max($intentos, 1), 6);
        $minutos = min(60, max(5, (int) pow(2, $escala)));

        return now()->addMinutes($minutos);
    }

    private function normalizarDocumento(string $identificacion): string
    {
        $limpio = preg_replace('/\D+/', '', $identificacion);

        return trim((string) $limpio);
    }

    private function limpiarTexto(?string $valor, int $maxLength = 65535): ?string
    {
        $texto = trim((string) $valor);
        if ($texto === '') {
            return null;
        }

        return mb_substr($texto, 0, $maxLength, 'UTF-8');
    }

    private function resumenError(DesbloqueoFicsPendiente $pendiente, string $error): array
    {
        return [
            'id' => $pendiente->id,
            'identificacion' => $pendiente->identificacion,
            'id_bloqueo_fics' => (int) $pendiente->id_bloqueo_fics,
            'intentos' => (int) $pendiente->intentos,
            'error' => $this->limpiarTexto($error, 500),
        ];
    }
}
