<?php

namespace App\Modules\SIG\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ImportSigDocumentosCommand extends Command
{
    protected $signature = 'sig:import-documentos {--limit=100 : Número máximo de registros a leer de Oracle para la prueba}';

    protected $description = 'Importa documentos y sus versiones desde ODIN.CAL_DOCUMENTOS (Oracle) hacia sig_documentos y sig_documento_versiones (MySQL).';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');

        $this->info("Leyendo datos desde Oracle (ODIN.CAL_DOCUMENTOS) límite {$limit}...");

        $rows = DB::connection('oracle')
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
            ->orderBy('codigo')
            ->orderBy('emision')
            ->limit($limit)
            ->get();

        if ($rows->isEmpty()) {
            $this->warn('No se encontraron registros en Oracle.');

            return self::SUCCESS;
        }

        $grouped = $rows->groupBy('codigo');
        $this->info("Procesando {$grouped->count()} códigos únicos...");

        $insertedDocs = 0;
        $insertedVersions = 0;

        foreach ($grouped as $codigo => $items) {

            $exists = DB::connection('mysql-gestion-admin')
                ->table('sig_documentos')
                ->where('codigo', $codigo)
                ->exists();

            if ($exists) {
                $this->line("Saltando código existente: {$codigo}");

                continue;
            }

            /** @var Collection $items */
            // Ordenar por emision desc y fecha para usar la mas reciente como base
            $ordenados = $items->sortByDesc(function ($row) {
                $emisionOrden = is_numeric($row->emision) ? (int) $row->emision : $row->emision;
                $fechaOrden = $row->fecmodifica ?? $row->feccreacion ?? $row->fecemision;

                return [$emisionOrden, $fechaOrden];
            })->values();

            $baseDoc = $ordenados->first();

            $documentoId = DB::connection('mysql-gestion-admin')
                ->table('sig_documentos')
                ->insertGetId([
                    'codigo' => $baseDoc->codigo,
                    'nombre' => $baseDoc->nombre,
                    'descripcion' => null,
                    'id_proceso' => $baseDoc->id_proceso,
                    'id_tipo_doc' => $baseDoc->id_tipo_doc,
                    'id_ubicacion' => $baseDoc->id_ubicacion,
                    'usrcreacion' => $baseDoc->usrcreacion,
                    'fechacreacion' => $baseDoc->feccreacion,
                    'usrmodifica' => $baseDoc->usrmodifica,
                    'fechamodifica' => $baseDoc->fecmodifica,
                ]);

            $insertedDocs++;

            // Agrupar por emision y conservar una fila por versión (prioriza la más reciente por fecha_modifica/creacion)
            $versionesPorEmision = $items
                ->groupBy('emision')
                ->map(function ($grupo) {
                    return $grupo->sortByDesc(function ($row) {
                        return $row->fecmodifica ?? $row->feccreacion ?? $row->fecemision;
                    })->first();
                });

            // Ordenar versiones por número de emision (descendente para identificar la vigente)
            $versionesOrdenadas = $versionesPorEmision
                ->sortByDesc(function ($row, $emision) {
                    return is_numeric($emision) ? (int) $emision : $emision;
                })
                ->values();

            $totalVersiones = $versionesOrdenadas->count();

            $versiones = $versionesOrdenadas->values()->map(function ($row, $index) use ($documentoId) {
                $estado = ($index === 0) ? 'APROBADO' : 'HISTORICO';

                return [
                    'documento_id' => $documentoId,
                    'version' => $row->emision,
                    'archivo_url' => $row->url,
                    'paginas' => $row->paginas,
                    'estado' => $estado,
                    'comentario_revision' => $row->observacion,
                    'id_elabora' => $row->id_elaboro,
                    'id_revisa' => $row->id_reviso,
                    'id_aprueba' => $row->id_aprobo,
                    'fecha_elaboracion' => $row->feccreacion,
                    'fecha_revision' => null,
                    'fecha_aprobacion' => $row->fecemision,
                    'fecha_modificacion' => $row->fecmodifica,
                ];
            })->all();

            DB::connection('mysql-gestion-admin')
                ->table('sig_documento_versiones')
                ->insert($versiones);

            $insertedVersions += count($versiones);
            $this->line("Insertado documento {$codigo} con ".count($versiones).' versiones.');
        }

        $this->info("Finalizado. Documentos insertados: {$insertedDocs}. Versiones insertadas: {$insertedVersions}.");

        return self::SUCCESS;
    }
}
