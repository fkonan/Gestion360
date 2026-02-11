<?php

namespace App\Modules\GestionRRHH\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BloqueoConductoresCommand extends Command
{
    protected $signature = 'bloqueo:conductores {--dry-run : Simula la ejecución sin insertar en la base de datos}';

    protected $description = 'Bloquea conductores activos de Copetran por falta de actualización de datos.';

    public function handle()
    {
        $dryRun = $this->option('dry-run');

        if (! $dryRun) {
            if (! $this->confirm('⚠️ ¿Seguro que quieres ejecutar la inserción real?')) {
                $this->warn('Operación cancelada.');

                return;
            }
        }

        $this->info('Obteniendo conductores activos desde la conexión oracle...');

        // Consulta de conductores activos (ORACLE)
        $conductores = DB::connection('oracle')->select('
        SELECT identificacion
        FROM per_contrato_persona cp
        JOIN per_empresapersonas ep ON cp.pe_id_pe = ep.pe_id_pe
        WHERE cp.cargo = 206
          AND ep.activo = 1
          AND ep.estborrado = 0
          AND ep.pe_id_emp = 6761
          AND ep.fecfin IS NULL
          AND tp_id = 11
    ');

        $total = count($conductores);
        $this->info("Total de conductores activos encontrados: {$total}");

        if ($total === 0) {
            $this->warn('No hay conductores activos para procesar.');

            return;
        }

        // Consulta de conductores que ya firmaron (MYSQL)
        $this->info('Obteniendo conductores que ya firmaron desde mysql-gestion-pasajes...');

        $firmados = DB::connection('mysql-gestion-pasajes')->select("
        SELECT o.DocNumPer
        FROM gestion_pasajes.otro_si o
        JOIN (
          SELECT DocNumPer, MAX(id) AS id_max
          FROM gestion_pasajes.otro_si
          WHERE CargoPer LIKE '%CONDUCTOR CARGA%'
          GROUP BY DocNumPer
        ) t ON o.id = t.id_max
    ");

        $firmadosDocs = array_map(fn ($f) => $f->DocNumPer, $firmados);
        $this->info('Conductores que ya firmaron: '.count($firmadosDocs));

        // Filtrar conductores activos que no han firmado
        $conductoresFiltrados = array_filter($conductores, function ($c) use ($firmadosDocs) {
            return ! in_array($c->identificacion, $firmadosDocs);
        });

        $totalFiltrados = count($conductoresFiltrados);
        $this->info("Total de conductores a bloquear (no firmaron): {$totalFiltrados}");

        if ($totalFiltrados === 0) {
            $this->warn('No hay conductores pendientes por bloquear.');

            return;
        }

        // MODO PRUEBAS: solo usar los 2 primeros registros
        $conductoresFiltrados = array_slice(array_values($conductoresFiltrados), 0, 2);
        $this->warn('⚠️ MODO PRUEBAS: solo se procesarán los primeros 2 conductores.');

        if ($dryRun) {
            $this->warn('🔍 MODO SIMULACIÓN ACTIVADO (--dry-run)');
            $this->line('No se insertará nada en la base de datos.');
            $this->line("Se habrían insertado {$totalFiltrados} bloqueos (en lotes de 1000).");
            $this->line('Ejemplo de los primeros 2 conductores:');
            foreach ($conductoresFiltrados as $c) {
                $this->line("- {$c->identificacion}");
            }

            return;
        }

        // ID de la persona que ejecuta el bloqueo
        $idPersonaBloquea = 1357442903; // ← reemplazar con el ID real

        $bloques = array_chunk($conductoresFiltrados, 1000);
        $totalInsertados = 0;
        $insertadosDocs = [];

        foreach ($bloques as $index => $grupo) {
            $this->info('Insertando lote '.($index + 1).' de '.count($grupo).' registros...');

            DB::connection('oracle')->beginTransaction();

            try {
                foreach ($grupo as $conductor) {
                    DB::connection('oracle')->insert("
              INSERT INTO PER_PERSONASBLOQUEO (
                  ID, CEDULA_CONDUCTOR, TB_ID, DESCRIPCION, PE_ID_BLOQUEO,
                  FECBLOQUEO, ACTIVO, PE_ID_DESBLOQUEO, FECDESBLOQUEO,
                  ESTBORRADO, FECMODIFICA, EMPMODIFICA, USRMODIFICA,
                  ROLMODIFICA, FECCREACION, EMPCREACION, USRCREACION,
                  FEC_INICIO, FEC_FIN
              )
              VALUES (
                  SEC_PER_PERSONASBLOQUEO.NEXTVAL,
                  :identificacionconductor,
                  75,
                  'REQUIERE ACTUALIZAR DATOS PERSONALES.',
                  :idpersonabloquea,
                  SYSDATE,
                  1,
                  NULL,
                  NULL,
                  0,
                  SYSDATE,
                  6831,
                  :idpersonabloquea,
                  60,
                  SYSDATE,
                  6831,
                  :idpersonabloquea,
                  SYSDATE,
                  NULL
              )
          ", [
                        'identificacionconductor' => $conductor->identificacion,
                        'idpersonabloquea' => $idPersonaBloquea,
                    ]);

                    $insertadosDocs[] = $conductor->identificacion;
                }

                DB::connection('oracle')->commit();

                $insertados = count($grupo);
                $totalInsertados += $insertados;
                $this->info('✅ Lote '.($index + 1)." insertado correctamente ({$insertados} registros).");

            } catch (\Exception $e) {
                DB::connection('oracle')->rollBack();
                $this->error('❌ Error en el lote '.($index + 1).': '.$e->getMessage());
            }
        }

        // Log personalizado
        $mensajeLog = '🕒 '.now()->format('Y-m-d H:i:s')." | Insertados: {$totalInsertados} | Documentos: ".implode(', ', $insertadosDocs);
        Log::build([
            'driver' => 'single',
            'path' => storage_path('logs/bloqueo_conductores.log'),
        ])->info($mensajeLog);

        $this->info("Proceso finalizado. Total de bloqueos insertados: {$totalInsertados}");
        $this->info('📝 Log guardado en storage/logs/bloqueo_conductores.log');
    }
}
