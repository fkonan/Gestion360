<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Services;

use App\Modules\Sarlaft\Models\Alerta;
use App\Modules\Sarlaft\Models\MantenimientoLog;
use Throwable;

class AlertaEscalationService
{
    public function __construct(
        private readonly GestionAlertaService $gestionAlertaService,
        private readonly PoliticaSarlaftService $politicaSarlaftService,
    ) {}

    /**
     * @return array{evaluadas:int,procesadas:int,omitidas:int,dry_run:bool,cutoff:string}
     */
    public function procesarVencidas(bool $dryRun = false, ?int $chunk = null): array
    {
        $inicio = microtime(true);
        $politica = $this->politicaSarlaftService->obtener();
        $cutoff = now()->subDays((int) ($politica['sla_dias_alerta'] ?? 1));
        $chunkSize = $this->resolverChunk($chunk);
        $riesgosAutoEscalables = (array) ($politica['auto_escalar_riesgos'] ?? ['alto', 'critico']);
        $autoEstado = (string) ($politica['auto_estado'] ?? 'en_revision');
        $autoUserId = $this->resolverUsuarioAutomatico($politica);

        $stats = [
            'evaluadas' => 0,
            'procesadas' => 0,
            'omitidas' => 0,
            'dry_run' => $dryRun,
            'cutoff' => $cutoff->toDateTimeString(),
        ];

        try {
            Alerta::query()
                ->where('estado', 'pendiente')
                ->whereIn('nivel_riesgo', $riesgosAutoEscalables)
                ->whereNotNull('created_at')
                ->where('created_at', '<=', $cutoff)
                ->where(function ($query): void {
                    $query->where('escalada_automatica', false)
                        ->orWhereNull('escalada_automatica');
                })
                ->orderBy('id')
                ->chunkById($chunkSize, function ($alertas) use (&$stats, $dryRun, $autoEstado, $autoUserId): void {
                    /** @var \Illuminate\Support\Collection<int, Alerta> $alertas */
                    foreach ($alertas as $alerta) {
                        $stats['evaluadas']++;

                        if ($dryRun) {
                            continue;
                        }

                        $this->gestionAlertaService->atender(
                            alerta: $alerta,
                            datos: [
                                'estado' => $autoEstado,
                                'notas' => $this->construirNotasAutomaticas($alerta->notas),
                            ],
                            userId: $autoUserId,
                            esAutomatica: true,
                        );

                        $stats['procesadas']++;
                    }
                }, 'id');

            $this->registrarLog(
                proceso: 'escalar_alertas_vencidas',
                estado: $stats['omitidas'] > 0 ? 'parcial' : 'exitoso',
                stats: $stats,
                duracionSegundos: (int) round(microtime(true) - $inicio),
                error: null,
            );
        } catch (Throwable $e) {
            $this->registrarLog(
                proceso: 'escalar_alertas_vencidas',
                estado: 'fallido',
                stats: $stats,
                duracionSegundos: (int) round(microtime(true) - $inicio),
                error: $e->getMessage(),
            );

            throw $e;
        }

        return $stats;
    }

    private function resolverChunk(?int $chunk): int
    {
        $configChunk = (int) config('sarlaft.archive_chunk', 5000);
        $valor = $chunk ?? $configChunk;

        return $valor > 0 ? $valor : 5000;
    }

    /**
     * @param  array<string, mixed>  $politica
     */
    private function resolverUsuarioAutomatico(array $politica): ?int
    {
        $userId = isset($politica['auto_user_id']) ? (int) $politica['auto_user_id'] : (int) config('sarlaft.auto_user_id', 1);

        return $userId > 0 ? $userId : null;
    }

    private function construirNotasAutomaticas(?string $notasActuales): string
    {
        $prefijo = '[AUTO] Escalada por SLA vencido (alerta sin atencion dentro del plazo).';

        if ($notasActuales === null || trim($notasActuales) === '') {
            return $prefijo;
        }

        return trim($notasActuales).PHP_EOL.$prefijo;
    }

    /**
     * @param  array{evaluadas:int,procesadas:int,omitidas:int,dry_run:bool,cutoff:string}  $stats
     */
    private function registrarLog(
        string $proceso,
        string $estado,
        array $stats,
        int $duracionSegundos,
        ?string $error,
    ): void {
        MantenimientoLog::query()->create([
            'proceso' => $proceso,
            'estado' => $estado,
            'registros_evaluados' => $stats['evaluadas'],
            'registros_procesados' => $stats['procesadas'],
            'registros_omitidos' => $stats['omitidas'],
            'duracion_segundos' => $duracionSegundos,
            'error_mensaje' => $error,
            'detalles' => [
                'dry_run' => $stats['dry_run'],
                'cutoff' => $stats['cutoff'],
            ],
            'created_at' => now(),
        ]);
    }
}
