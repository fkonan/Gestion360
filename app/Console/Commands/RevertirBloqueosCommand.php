<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RevertirBloqueosCommand extends Command
{
    protected $signature = 'bloqueo:revertir {--dry-run : Simula la eliminación sin afectar la base de datos}';
    protected $description = 'Elimina los bloqueos creados en PE_PersonalEstados para reversar el proceso anterior. (solo bloqueos 28, 31, 32, 34)';

    public function handle()
    {
        $dryRun = $this->option('dry-run');

        if (!$dryRun) {
            if (!$this->confirm('⚠️ ¿Seguro que quieres eliminar los bloqueos? Esta acción no se puede deshacer.')) {
                $this->warn('Operación cancelada.');
                return;
            }
        }

        $this->info('🔹 Conectando a SQL Server...');
        try {
            // Contar cuántos bloqueos hay actualmente
            $totalBloqueos = DB::connection('sqlsrv')
                ->table('PE_PersonalEstados')
                ->whereIn('PersonalEstadoTipoID', [28, 31, 32, 34])
                ->count();

            if ($totalBloqueos === 0) {
                $this->warn('No hay bloqueos activos para eliminar.');
                return;
            }

            $this->info("Se encontraron {$totalBloqueos} registros a eliminar.");

            if ($dryRun) {
                $this->warn('🔍 MODO SIMULACIÓN ACTIVADO (--dry-run)');
                $this->line("No se eliminará nada. Se habrían eliminado {$totalBloqueos} registros.");
                return;
            }

            DB::connection('sqlsrv')->beginTransaction();

            $this->info('🚀 Eliminando bloqueos...');
            DB::connection('sqlsrv')
                ->table('PE_PersonalEstados')
                ->whereIn('PersonalEstadoTipoID', [28, 31, 32, 34])
                ->delete();

            DB::connection('sqlsrv')->commit();

            $this->info("✅ Bloqueos eliminados correctamente: {$totalBloqueos}");

            // Registrar log
            $mensajeLog = "🕒 " . now()->format('Y-m-d H:i:s') . " | Bloqueos revertidos: {$totalBloqueos}";

            Log::build([
                'driver' => 'single',
                'path' => storage_path('logs/revertir_bloqueos.log'),
            ])->info($mensajeLog);

            $this->info("📝 Log guardado en storage/logs/revertir_bloqueos.log");

        } catch (\Exception $e) {
            DB::connection('sqlsrv')->rollBack();
            $this->error('❌ Error al eliminar los bloqueos: ' . $e->getMessage());
        }
    }
}
