<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Console\Commands;

use App\Modules\Sarlaft\Services\AlertaEscalationService;
use Illuminate\Console\Command;

class ProcesarAlertasVencidasCommand extends Command
{
    protected $signature = 'sarlaft:procesar-alertas-vencidas
        {--dry-run : Simula el proceso sin aplicar cambios}
        {--chunk= : Tamano de lote para procesamiento}';

    protected $description = 'Escala alertas SARLAFT vencidas y aplica decision automatica de bloqueo';

    public function __construct(
        private readonly AlertaEscalationService $alertaEscalationService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $chunk = $this->option('chunk') !== null ? (int) $this->option('chunk') : null;

        $stats = $this->alertaEscalationService->procesarVencidas($dryRun, $chunk);

        $this->info('Proceso de alertas vencidas finalizado.');
        $this->line('Cutoff: '.$stats['cutoff']);
        $this->line('Evaluadas: '.$stats['evaluadas']);
        $this->line('Procesadas: '.$stats['procesadas']);
        $this->line('Omitidas: '.$stats['omitidas']);
        $this->line('Dry run: '.($stats['dry_run'] ? 'si' : 'no'));

        return self::SUCCESS;
    }
}


