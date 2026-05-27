<?php

namespace App\Modules\GestionRRHH\Console\Commands;

use App\Modules\GestionRRHH\Services\DesbloqueoFicsPendienteService;
use Illuminate\Console\Command;

class ReprocesarDesbloqueosFicsCommand extends Command
{
    protected $signature = 'bloqueo:reprocesar-desbloqueos-fics
        {--limit=200 : Maximo de pendientes a procesar por ejecucion}
        {--identificacion= : Procesa solo la identificacion indicada}
        {--forzar : Ignora proximo_intento_at y fuerza procesamiento}
        {--dry-run : Solo muestra cuantos pendientes se procesarian}
        {--max-intentos=24 : Limite de intentos antes de mantener en estado fallido}';

    protected $description = 'Reprocesa pendientes de desbloqueo FICS guardados localmente en MySQL.';

    public function __construct(private readonly DesbloqueoFicsPendienteService $service)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $identificacion = $this->option('identificacion');
        $forzar = (bool) $this->option('forzar');
        $dryRun = (bool) $this->option('dry-run');
        $maxIntentos = (int) $this->option('max-intentos');

        $resultado = $this->service->reprocesarPendientes(
            limit: $limit,
            identificacion: is_string($identificacion) ? $identificacion : null,
            forzar: $forzar,
            dryRun: $dryRun,
            maxIntentos: $maxIntentos
        );

        $this->info('Pendientes seleccionados: '.$resultado['seleccionados']);
        $this->info('Procesados: '.$resultado['procesados']);
        $this->info('Resueltos: '.$resultado['resueltos']);
        $this->info('Fallidos: '.$resultado['fallidos']);
        $this->info('Reprogramados: '.$resultado['reprogramados']);
        $this->info('Omitidos por concurrencia: '.$resultado['omitidos']);

        if ($dryRun) {
            $this->warn('Ejecucion en modo simulacion (--dry-run).');
        }

        if (! empty($resultado['errores'])) {
            $this->warn('Se presentaron errores durante el reproceso:');
            foreach (array_slice($resultado['errores'], 0, 20) as $item) {
                $this->line(sprintf(
                    '- ID %d | Doc %s | Bloqueo %d | Intentos %d | %s',
                    (int) ($item['id'] ?? 0),
                    (string) ($item['identificacion'] ?? ''),
                    (int) ($item['id_bloqueo_fics'] ?? 0),
                    (int) ($item['intentos'] ?? 0),
                    (string) ($item['error'] ?? '')
                ));
            }
        }

        return self::SUCCESS;
    }
}
