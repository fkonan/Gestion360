<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Console\Commands;

use App\Modules\Sarlaft\Models\SistemaConsumidor;
use App\Modules\Sarlaft\Services\IntentoOperacionService;
use Illuminate\Console\Command;

class LeerIntentosDbCommand extends Command
{
    protected $signature = 'sarlaft:leer-intentos-db
                            {--sistema= : Codigo del sistema consumidor especifico (opcional)}
                            {--fecha-desde= : Fecha/hora inicial del filtro (ISO-8601)}
                            {--fecha-hasta= : Fecha/hora final del filtro (ISO-8601)}';

    protected $description = 'Lee directamente las tablas de operaciones de los sistemas inhouse (modo db) y registra intentos de operacion con coincidencias en listas.';

    public function __construct(
        private readonly IntentoOperacionService $service,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $query = SistemaConsumidor::query()
            ->where('estado', 'activo')
            ->where('modo_integracion', 'db')
            ->whereNotNull('db_conexion')
            ->whereNotNull('db_tabla');

        if ($this->option('sistema')) {
            $query->where('codigo', $this->option('sistema'));
        }

        $sistemas = $query->get();

        if ($sistemas->isEmpty()) {
            $this->info('No hay sistemas con lectura directa de BD configurada.');

            return self::SUCCESS;
        }

        $totalRegistrados = 0;
        $fechaDesde = $this->option('fecha-desde') ? (string) $this->option('fecha-desde') : null;
        $fechaHasta = $this->option('fecha-hasta') ? (string) $this->option('fecha-hasta') : null;

        foreach ($sistemas as $sistema) {
            $this->line("Leyendo sistema: <comment>{$sistema->nombre}</comment> ({$sistema->codigo})...");

            $registrados = $this->service->ejecutarLecturaDb($sistema, $fechaDesde, $fechaHasta);

            $this->line("  → {$registrados} intento(s) registrado(s).");
            $totalRegistrados += $registrados;
        }

        $this->info("Lectura DB completada. Total de intentos registrados: {$totalRegistrados}.");

        return self::SUCCESS;
    }
}
