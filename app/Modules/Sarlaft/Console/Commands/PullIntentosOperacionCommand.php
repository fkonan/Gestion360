<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Console\Commands;

use App\Modules\Sarlaft\Models\SistemaConsumidor;
use App\Modules\Sarlaft\Services\IntentoOperacionService;
use Illuminate\Console\Command;

class PullIntentosOperacionCommand extends Command
{
    protected $signature = 'sarlaft:pull-intentos
                            {--sistema= : Codigo del sistema consumidor especifico (opcional)}';

    protected $description = 'Consulta los endpoints Pull de sistemas consumidores y registra intentos de operacion con coincidencias en listas.';

    public function __construct(
        private readonly IntentoOperacionService $service,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $query = SistemaConsumidor::query()
            ->where('estado', 'activo')
            ->whereNotNull('pull_endpoint');

        if ($this->option('sistema')) {
            $query->where('codigo', $this->option('sistema'));
        }

        $sistemas = $query->get();

        if ($sistemas->isEmpty()) {
            $this->info('No hay sistemas con Pull configurado.');

            return self::SUCCESS;
        }

        $totalRegistrados = 0;

        foreach ($sistemas as $sistema) {
            $this->line("Consultando sistema: <comment>{$sistema->nombre}</comment> ({$sistema->codigo})...");

            $registrados = $this->service->ejecutarPull($sistema);

            $this->line("  → {$registrados} intento(s) registrado(s).");
            $totalRegistrados += $registrados;
        }

        $this->info("Pull completado. Total de intentos registrados: {$totalRegistrados}.");

        return self::SUCCESS;
    }
}
