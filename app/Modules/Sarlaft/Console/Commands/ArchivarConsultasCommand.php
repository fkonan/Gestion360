<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Console\Commands;

use App\Modules\Sarlaft\Services\ConsultaArchiveService;
use Illuminate\Console\Command;

class ArchivarConsultasCommand extends Command
{
    protected $signature = 'sarlaft:archivar-consultas
        {--dry-run : Simula el proceso sin mover ni borrar datos}
        {--chunk= : Tamano de lote para el archivado}';

    protected $description = 'Archiva consultas SARLAFT negativas vencidas segun politica de retencion';

    public function __construct(
        private readonly ConsultaArchiveService $consultaArchiveService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $chunk = $this->option('chunk') !== null ? (int) $this->option('chunk') : null;

        $stats = $this->consultaArchiveService->archivarNegativas($dryRun, $chunk);

        $this->info('Proceso de archivado de consultas finalizado.');
        $this->line('Cutoff: '.$stats['cutoff']);
        $this->line('Evaluadas: '.$stats['evaluadas']);
        $this->line('Procesadas: '.$stats['procesadas']);
        $this->line('Omitidas: '.$stats['omitidas']);
        $this->line('Dry run: '.($stats['dry_run'] ? 'si' : 'no'));

        return self::SUCCESS;
    }
}
