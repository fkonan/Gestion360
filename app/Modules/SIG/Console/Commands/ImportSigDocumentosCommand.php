<?php

namespace App\Modules\SIG\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use stdClass;

class ImportSigDocumentosCommand extends Command
{
    protected $signature = 'sig:import-documentos
        {--limit=0 : Maximo de codigos a leer de Oracle (0 = sin limite)}
        {--chunk=200 : Cantidad de codigos por lote}
        {--from-code= : Procesa codigos >= a este valor}';

    protected $description = 'Importa documentos desde ODIN.CAL_DOCUMENTOS (Oracle) sincronizando versiones faltantes en sig_documentos y sig_documento_versiones (MySQL).';

    public function handle(): int
    {
        $limit = max(0, (int) $this->option('limit'));
        $chunkSize = max(1, (int) $this->option('chunk'));
        $fromCode = trim((string) $this->option('from-code'));

        $this->info('Preparando codigos desde Oracle (ODIN.CAL_DOCUMENTOS)...');

        $codigosQuery = DB::connection('oracle')
            ->table(DB::raw('ODIN.CAL_DOCUMENTOS'))
            ->select('codigo')
            ->distinct()
            ->orderBy('codigo');

        if ($fromCode !== '') {
            $codigosQuery->where('codigo', '>=', $fromCode);
        }

        if ($limit > 0) {
            $codigosQuery->limit($limit);
        }

        $codigos = $codigosQuery
            ->pluck('codigo')
            ->filter(fn ($codigo) => $codigo !== null && trim((string) $codigo) !== '')
            ->values();

        if ($codigos->isEmpty()) {
            $this->warn('No se encontraron codigos para procesar en Oracle.');

            return self::SUCCESS;
        }

        $this->info("Codigos a procesar: {$codigos->count()}. Tamano lote: {$chunkSize}.");

        $documentosNuevos = 0;
        $documentosExistentes = 0;
        $versionesInsertadas = 0;
        $versionesYaExistentes = 0;
        $versionesActualizadasUsrCreacion = 0;
        $codigosSinFilas = 0;

        foreach ($codigos->chunk($chunkSize) as $loteCodigos) {
            $listaCodigos = $loteCodigos->values()->all();

            $rowsByCodigo = DB::connection('oracle')
                ->table(DB::raw('ODIN.CAL_DOCUMENTOS'))
                ->select([
                    'codigo',
                    'nombre',
                    'id_proceso',
                    'id_tipo_doc',
                    'id_ubicacion',
                    'usrcreacion',
                    'feccreacion',
                    'usrmodifica',
                    'fecmodifica',
                    'emision',
                    'url',
                    'paginas',
                    'observacion',
                    'id_elaboro',
                    'id_reviso',
                    'id_aprobo',
                    'fecemision',
                ])
                ->whereIn('codigo', $listaCodigos)
                ->orderBy('codigo')
                ->orderBy('emision')
                ->get()
                ->groupBy('codigo');

            foreach ($listaCodigos as $codigo) {
                /** @var Collection $items */
                $items = $rowsByCodigo->get($codigo, collect());

                if ($items->isEmpty()) {
                    $codigosSinFilas++;
                    $this->warn("Codigo {$codigo} sin filas en Oracle; se omite.");

                    continue;
                }

                $resultado = $this->sincronizarCodigo((string) $codigo, $items);

                $documentosNuevos += $resultado['documento_nuevo'] ? 1 : 0;
                $documentosExistentes += $resultado['documento_nuevo'] ? 0 : 1;
                $versionesInsertadas += $resultado['versiones_insertadas'];
                $versionesYaExistentes += $resultado['versiones_ya_existentes'];
                $versionesActualizadasUsrCreacion += $resultado['versiones_actualizadas_usrcreacion'];

                $this->line(
                    "Codigo {$codigo}: ".($resultado['documento_nuevo'] ? 'documento nuevo' : 'documento existente')
                    .", versiones insertadas {$resultado['versiones_insertadas']}"
                    .", versiones ya existentes {$resultado['versiones_ya_existentes']}"
                    .", usrcreacion actualizado {$resultado['versiones_actualizadas_usrcreacion']}."
                );
            }
        }

        $this->info(
            'Finalizado. '.
            "Documentos nuevos: {$documentosNuevos}. ".
            "Documentos existentes: {$documentosExistentes}. ".
            "Versiones insertadas: {$versionesInsertadas}. ".
            "Versiones ya existentes: {$versionesYaExistentes}. ".
            "Usrcreacion actualizado en versiones: {$versionesActualizadasUsrCreacion}. ".
            "Codigos sin filas: {$codigosSinFilas}."
        );

        return self::SUCCESS;
    }

    /**
     * @return array{documento_nuevo: bool, versiones_insertadas: int, versiones_ya_existentes: int, versiones_actualizadas_usrcreacion: int}
     */
    private function sincronizarCodigo(string $codigo, Collection $items): array
    {
        return DB::connection('mysql-gestion-admin')->transaction(function () use ($codigo, $items) {
            $documentoBase = $this->obtenerDocumentoBase($items);

            $doc = DB::connection('mysql-gestion-admin')
                ->table('sig_documentos')
                ->where('codigo', $codigo)
                ->lockForUpdate()
                ->first(['id']);

            $documentoNuevo = false;

            if ($doc) {
                $documentoId = (int) $doc->id;
            } else {
                $documentoId = DB::connection('mysql-gestion-admin')
                    ->table('sig_documentos')
                    ->insertGetId([
                        'codigo' => $documentoBase->codigo,
                        'nombre' => $documentoBase->nombre,
                        'descripcion' => null,
                        'id_proceso' => $documentoBase->id_proceso,
                        'id_tipo_doc' => $documentoBase->id_tipo_doc,
                        'id_ubicacion' => $documentoBase->id_ubicacion,
                        'usrcreacion' => $documentoBase->usrcreacion,
                        'fechacreacion' => $documentoBase->feccreacion,
                        'usrmodifica' => $documentoBase->usrmodifica,
                        'fechamodifica' => $documentoBase->fecmodifica,
                    ]);
                $documentoNuevo = true;
            }

            $versionesOrigen = $this->obtenerVersionesOrigen($items);
            $versionMaximaOrigen = $this->normalizarVersion($versionesOrigen->first()?->emision);

            $versionesDestino = DB::connection('mysql-gestion-admin')
                ->table('sig_documento_versiones')
                ->where('documento_id', $documentoId)
                ->get(['id', 'version', 'estado', 'usrcreacion']);

            $versionesDestinoPorNumero = $versionesDestino
                ->filter(fn ($version) => $version->version !== null)
                ->groupBy(fn ($version) => (string) $this->normalizarVersion($version->version));

            $tieneAprobada = $versionesDestino->contains(fn ($version) => $version->estado === 'APROBADO');

            $versionesInsertar = [];
            $versionesYaExistentes = 0;
            $versionesActualizadasUsrCreacion = 0;

            foreach ($versionesOrigen as $row) {
                $version = $this->normalizarVersion($row->emision);

                if ($versionesDestinoPorNumero->has((string) $version)) {
                    $versionesYaExistentes++;

                    if ($row->usrcreacion !== null) {
                        $actualizadas = DB::connection('mysql-gestion-admin')
                            ->table('sig_documento_versiones')
                            ->where('documento_id', $documentoId)
                            ->where('version', $version)
                            ->whereNull('usrcreacion')
                            ->update([
                                'usrcreacion' => $row->usrcreacion,
                            ]);

                        $versionesActualizadasUsrCreacion += (int) $actualizadas;
                    }

                    continue;
                }

                $estado = 'HISTORICO';

                if (! $tieneAprobada && $version === $versionMaximaOrigen) {
                    $estado = 'APROBADO';
                    $tieneAprobada = true;
                }

                $versionesInsertar[] = [
                    'documento_id' => $documentoId,
                    'version' => $version,
                    'archivo_url' => $row->url,
                    'paginas' => $row->paginas,
                    'estado' => $estado,
                    'comentario_revision' => $row->observacion,
                    'id_elabora' => $row->id_elaboro,
                    'id_revisa' => $row->id_reviso,
                    'id_aprueba' => $row->id_aprobo,
                    'usrcreacion' => $row->usrcreacion,
                    'fecha_elaboracion' => $row->feccreacion,
                    'fecha_revision' => null,
                    'fecha_aprobacion' => $row->fecemision,
                    'fecha_modificacion' => $row->fecmodifica,
                ];
            }

            if (! empty($versionesInsertar)) {
                DB::connection('mysql-gestion-admin')
                    ->table('sig_documento_versiones')
                    ->insert($versionesInsertar);
            }

            return [
                'documento_nuevo' => $documentoNuevo,
                'versiones_insertadas' => count($versionesInsertar),
                'versiones_ya_existentes' => $versionesYaExistentes,
                'versiones_actualizadas_usrcreacion' => $versionesActualizadasUsrCreacion,
            ];
        });
    }

    private function obtenerDocumentoBase(Collection $items): stdClass
    {
        /** @var stdClass $base */
        $base = $items
            ->sortByDesc(fn ($row) => $this->claveRecencia($row->emision, $row->fecmodifica ?? $row->feccreacion ?? $row->fecemision))
            ->first();

        return $base;
    }

    private function obtenerVersionesOrigen(Collection $items): Collection
    {
        return $items
            ->groupBy('emision')
            ->map(function (Collection $grupo) {
                return $grupo
                    ->sortByDesc(fn ($row) => $this->normalizarFecha($row->fecmodifica ?? $row->feccreacion ?? $row->fecemision))
                    ->first();
            })
            ->sortByDesc(fn ($row, $emision) => $this->normalizarVersion($emision))
            ->values();
    }

    private function claveRecencia(mixed $emision, mixed $fecha): string
    {
        return sprintf('%010d|%s', $this->normalizarVersion($emision), $this->normalizarFecha($fecha));
    }

    private function normalizarVersion(mixed $emision): int
    {
        return is_numeric($emision) ? (int) $emision : 0;
    }

    private function normalizarFecha(mixed $fecha): string
    {
        if ($fecha === null) {
            return '0000-00-00 00:00:00';
        }

        return substr((string) $fecha, 0, 19);
    }
}
