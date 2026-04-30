<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Services;

use App\Modules\Sarlaft\Models\Consulta;
use App\Modules\Sarlaft\Models\ConsultaArchivo;
use App\Modules\Sarlaft\Models\MantenimientoLog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class ConsultaArchiveService
{
    /**
     * @return array{evaluadas:int,procesadas:int,omitidas:int,dry_run:bool,cutoff:string}
     */
    public function archivarNegativas(bool $dryRun = false, ?int $chunk = null): array
    {
        $inicio = microtime(true);
        $chunkSize = $this->resolverChunk($chunk);
        $cutoff = now()->subDays((int) config('sarlaft.retencion_negativas_dias', 180));

        $stats = [
            'evaluadas' => 0,
            'procesadas' => 0,
            'omitidas' => 0,
            'dry_run' => $dryRun,
            'cutoff' => $cutoff->toDateTimeString(),
        ];

        try {
            $this->queryConsultasNegativas($cutoff->toDateTimeString())
                ->orderBy('consultas.id')
                ->chunkById($chunkSize, function (Collection $consultas) use (&$stats, $dryRun): void {
                    $ids = $consultas->pluck('id')->map(static fn (mixed $id): int => (int) $id)->all();
                    $stats['evaluadas'] += count($ids);

                    if ($dryRun || $ids === []) {
                        return;
                    }

                    DB::connection('mysql-sarlaft')->transaction(function () use ($ids, &$stats): void {
                        $filasArchivo = $this->mapearConsultasParaArchivo($ids);

                        if ($filasArchivo === []) {
                            $stats['omitidas'] += count($ids);

                            return;
                        }

                        ConsultaArchivo::query()->insertOrIgnore($filasArchivo);

                        $idsArchivados = ConsultaArchivo::query()
                            ->whereIn('consulta_id_original', $ids)
                            ->pluck('consulta_id_original')
                            ->map(static fn (mixed $id): int => (int) $id)
                            ->all();

                        if ($idsArchivados === []) {
                            $stats['omitidas'] += count($ids);

                            return;
                        }

                        $eliminados = Consulta::query()->whereIn('id', $idsArchivados)->delete();

                        $stats['procesadas'] += $eliminados;
                        $stats['omitidas'] += max(count($ids) - $eliminados, 0);
                    });
                }, 'consultas.id', 'id');

            $this->registrarLog(
                proceso: 'archivar_consultas',
                estado: $stats['omitidas'] > 0 ? 'parcial' : 'exitoso',
                stats: $stats,
                duracionSegundos: (int) round(microtime(true) - $inicio),
                error: null,
            );
        } catch (Throwable $e) {
            $this->registrarLog(
                proceso: 'archivar_consultas',
                estado: 'fallido',
                stats: $stats,
                duracionSegundos: (int) round(microtime(true) - $inicio),
                error: $e->getMessage(),
            );

            throw $e;
        }

        return $stats;
    }

    /**
     * @return \Illuminate\Database\Query\Builder
     */
    private function queryConsultasNegativas(string $cutoff)
    {
        return DB::connection('mysql-sarlaft')
            ->table('sarlaft_consultas as consultas')
            ->leftJoin('sarlaft_alertas as alertas', function ($join): void {
                $join->on('alertas.consulta_id', '=', 'consultas.id')
                    ->whereNull('alertas.deleted_at');
            })
            ->whereNull('alertas.id')
            ->where('consultas.encontrado', false)
            ->where('consultas.presta_servicio', true)
            ->where('consultas.nivel_riesgo', 'ninguno')
            ->whereNotNull('consultas.created_at')
            ->where('consultas.created_at', '<', $cutoff)
            ->select('consultas.id');
    }

    /**
     * @param  array<int, int>  $ids
     * @return array<int, array<string, mixed>>
     */
    private function mapearConsultasParaArchivo(array $ids): array
    {
        $consultas = Consulta::query()
            ->whereIn('id', $ids)
            ->get();

        return $consultas
            ->map(static function (Consulta $consulta): array {
                return [
                    'consulta_id_original' => $consulta->id,
                    'sistema_origen' => $consulta->sistema_origen,
                    'tipo_documento' => $consulta->tipo_documento,
                    'numero_documento' => $consulta->numero_documento,
                    'nombre_consultado' => $consulta->nombre_consultado,
                    'encontrado' => $consulta->encontrado,
                    'presta_servicio' => $consulta->presta_servicio,
                    'nivel_riesgo' => $consulta->nivel_riesgo,
                    'coincidencias' => $consulta->getRawOriginal('coincidencias'),
                    'contexto_operacion' => $consulta->getRawOriginal('contexto_operacion'),
                    'ip_origen' => $consulta->ip_origen,
                    'created_at' => $consulta->created_at,
                    'archived_at' => now(),
                    'motivo_archivo' => 'retencion_negativa_180d',
                ];
            })
            ->values()
            ->all();
    }

    private function resolverChunk(?int $chunk): int
    {
        $configChunk = (int) config('sarlaft.archive_chunk', 5000);
        $valor = $chunk ?? $configChunk;

        return $valor > 0 ? $valor : 5000;
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
