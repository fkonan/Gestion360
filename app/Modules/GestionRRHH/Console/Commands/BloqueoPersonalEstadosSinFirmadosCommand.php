<?php

namespace App\Modules\GestionRRHH\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BloqueoPersonalEstadosSinFirmadosCommand extends Command
{
    protected $signature = 'bloqueo:personal-estados-sin-firmados {--dry-run : Simula la ejecucion sin insertar en la base de datos}';
    protected $description = 'Genera bloqueos en PE_PersonalEstados para tripulantes activos sin validar firmas desde MySQL.';

    public function handle()
    {
        $dryRun = $this->option('dry-run');

        if (!$dryRun) {
            if (!$this->confirm('Seguro que quieres ejecutar la insercion real?')) {
                $this->warn('Operacion cancelada.');
                return;
            }
        }

        $this->info('Obteniendo conductores activos desde Oracle...');
        $conductoresOracle = DB::connection('oracle')->select("
            SELECT DISTINCT
                EP.PE_ID_PE AS persona_id,
                P.IDENTIFICACION AS identificacion,
                P.PNOMBRE || ' ' || P.SNOMBRE || ' ' || P.PAPELLIDO || ' ' || P.SAPELLIDO AS nombre
            FROM PER_EMPRESAPERSONAS EP
            INNER JOIN PER_TIPOPERFIL TP ON TP.ID = EP.TP_ID
            INNER JOIN PER_PERSONAS P ON (P.ID = EP.PE_ID_PE AND P.ESTBORRADO = 0 AND P.ESTADO = 'ACTIVO')
            INNER JOIN PER_CONTRATO_PERSONA D ON P.ID = D.PE_ID_PE
            WHERE EP.TP_ID IN (11)
              AND EP.ACTIVO = 1
              AND EP.ESTBORRADO = 0
        ");
        $this->info("Conductores activos encontrados en Oracle: " . count($conductoresOracle));

        if (empty($conductoresOracle)) {
            $this->warn('No se encontraron conductores activos en Oracle.');
            return;
        }

        $this->info('Obteniendo tripulantes desde SQL Server...');
        $tripulantes = DB::connection('sqlsrv')->select("SELECT id, Documento FROM Tripulantes");
        $this->info('Tripulantes encontrados: ' . count($tripulantes));

        $docsConductores = array_map(fn($c) => $c->identificacion, $conductoresOracle);
        $tripulantesFiltrados = array_filter($tripulantes, function ($t) use ($docsConductores) {
            return in_array($t->Documento, $docsConductores);
        });
        $tripulantesFiltrados = array_values($tripulantesFiltrados);
        $totalFiltrados = count($tripulantesFiltrados);
        $this->info("Tripulantes coincidentes a bloquear: {$totalFiltrados}");

        if ($totalFiltrados === 0) {
            $this->warn('No hay tripulantes coincidentes para insertar bloqueos.');
            return;
        }

        if ($dryRun) {
            $this->warn('MODO SIMULACION ACTIVADO (--dry-run)');
            $this->line("No se insertara nada en la base de datos.");
            $this->line("Se habrian insertado " . ($totalFiltrados) . " registros.");
            $this->line("Ejemplo de los primeros 3 tripulantes:");
            foreach (array_slice($tripulantesFiltrados, 0, 3) as $t) {
                $this->line("- ID: {$t->id}, Documento: {$t->Documento}");
            }
            return;
        }

        $this->info('Insertando bloqueos en PE_PersonalEstados (SQL Server)...');
        $bloques = array_chunk($tripulantesFiltrados, 1000);
        $tiposBloqueo = [34];
        $totalInsertados = 0;

        foreach ($bloques as $index => $grupo) {
            $this->info("Procesando lote " . ($index + 1) . " de " . count($grupo) . " tripulantes...");

            DB::connection('sqlsrv')->beginTransaction();

            try {
                foreach ($grupo as $tripulante) {
                    foreach ($tiposBloqueo as $tipo) {
                        DB::connection('sqlsrv')->insert("
                            INSERT INTO PE_PersonalEstados (
                                PersonalID,
                                PersonalEstadoTipoID,
                                FechaInicio,
                                FechaFinalizacion,
                                Descontado,
                                PersonalReemplazoID,
                                Cantidad,
                                PersonalEstadoParentID
                            )
                            VALUES (
                                :personal_id,
                                :tipo_estado,
                                GETDATE(),
                                DATEADD(YEAR, 1, GETDATE()),
                                0,
                                NULL,
                                0.00,
                                NULL
                            )
                        ", [
                            'personal_id' => $tripulante->id,
                            'tipo_estado' => $tipo,
                        ]);
                        $totalInsertados++;
                    }
                }

                DB::connection('sqlsrv')->commit();
                $this->info("Lote " . ($index + 1) . " insertado correctamente.");
            } catch (\Exception $e) {
                DB::connection('sqlsrv')->rollBack();
                $this->error("Error en el lote " . ($index + 1) . ": " . $e->getMessage());
            }
        }

        $mensajeLog = " " . now()->format('Y-m-d H:i:s') .
            " | Total bloqueos insertados: {$totalInsertados} | Tripulantes: {$totalFiltrados}";

        Log::build([
            'driver' => 'single',
            'path' => storage_path('logs/bloqueo_personal_estados.log'),
        ])->info($mensajeLog);

        $this->info("Proceso finalizado. Total de bloqueos insertados: {$totalInsertados}");
        $this->info("Log guardado en storage/logs/bloqueo_personal_estados.log");
    }
}
