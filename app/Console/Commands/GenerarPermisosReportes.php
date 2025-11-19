<?php

namespace App\Console\Commands;

use App\Models\GESTIONADMIN\Reporteador;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

class GenerarPermisosReportes extends Command
{
    protected $signature = 'reportes:generar-permisos';

    protected $description = 'Genera un permiso individual para cada reporte registrado en la tabla reportes';

    public function handle()
    {
        $reportes = Reporteador::get();

        $this->info("Generando permisos para {$reportes->count()} reportes...");

        foreach ($reportes as $reporte) {
            $nombrePermiso = "administracion.reportes.id_{$reporte->id}";

            // Crear permiso si no existe
            Permission::firstOrCreate(
                ['name' => $nombrePermiso, 'guard_name' => 'web']
            );

            $this->line("✔ Permiso creado o existente: {$nombrePermiso}");
        }

        $this->info("Proceso completado correctamente.");
    }
}
