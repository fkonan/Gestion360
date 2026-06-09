<?php

namespace App\Modules\PagosRecaudos\Console\Commands;

use App\Modules\PagosRecaudos\Services\Cajasan\PagoRegularizacionService;
use App\Modules\PagosRecaudos\Services\Cajasan\PagoProcesoLockService;
use Illuminate\Console\Command;
use Throwable;

class RegularizarPagoCajasanCommand extends Command
{
    protected $signature = 'pagos:regularizar-cajasan
        {detalle_id : ID en CON_DETCARGUEPAGOSYRECAUDOS}
        {authorization_code : Codigo de autorizacion confirmado externamente}
        {--user-id= : ID de PER_PERSONAS del usuario que regulariza (obligatorio)}
        {--telefono=0 : Telefono para CON_DETALLEPAGORECAUDO}
        {--turno-id= : ID de TES_CAJATURNOS (opcional, fuerza turno)}
        {--force : Permite ejecutar sin confirmacion interactiva}
        {--dry-run : Solo valida y muestra datos, no escribe cambios}';

    protected $description = 'Regulariza un pago Cajasan en estado C cuando externamente fue aprobado y fallo la persistencia local.';

    public function __construct(
        private readonly PagoProcesoLockService $lockService,
        private readonly PagoRegularizacionService $regularizacionService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $detalleId = (int) $this->argument('detalle_id');
        $authorizationCode = trim((string) $this->argument('authorization_code'));
        $userId = (int) $this->option('user-id');
        $telefono = trim((string) $this->option('telefono'));
        $turnoId = $this->option('turno-id') !== null ? (int) $this->option('turno-id') : null;
        $force = (bool) $this->option('force');
        $dryRun = (bool) $this->option('dry-run');

        if ($detalleId <= 0) {
            $this->error('detalle_id invalido.');

            return self::FAILURE;
        }

        if ($authorizationCode === '') {
            $this->error('authorization_code es obligatorio.');

            return self::FAILURE;
        }

        if ($userId <= 0) {
            $this->error('--user-id es obligatorio y debe ser un ID valido de PER_PERSONAS.');

            return self::FAILURE;
        }

        if (! $force && ! $dryRun) {
            $ok = $this->confirm("Se regularizara el detalle {$detalleId} y se marcara como pagado. Desea continuar?");
            if (! $ok) {
                $this->warn('Operacion cancelada.');

                return self::INVALID;
            }
        }

        try {
            if ($dryRun) {
                $resultado = $this->regularizacionService->previsualizar(
                    detalleId: $detalleId,
                    authorizationCode: $authorizationCode,
                    userIdRegulariza: $userId,
                    telefono: $telefono === '' ? '0' : $telefono,
                    turnoIdForzado: $turnoId,
                );
            } else {
                $resultado = $this->lockService->runSequenceCriticalSection(function () use ($detalleId, $authorizationCode, $userId, $telefono, $turnoId) {
                    return $this->regularizacionService->regularizar(
                        detalleId: $detalleId,
                        authorizationCode: $authorizationCode,
                        userIdRegulariza: $userId,
                        telefono: $telefono === '' ? '0' : $telefono,
                        turnoIdForzado: $turnoId,
                    );
                });
            }

            $this->info('Detalle: '.(string) $resultado['detalle_id']);
            $this->info('Estado inicial: '.(string) $resultado['estado_inicial']);
            $this->info('Turno: '.(string) $resultado['turno_id']);
            $this->info('Sucursal: '.(string) $resultado['idsucursal']);
            $this->info('EN_ID: '.(string) $resultado['en_id']);
            $this->info('Valor: '.(string) $resultado['saldo']);
            $this->info('Centro costo: '.(string) $resultado['centro_costo']);
            $this->info('Cliente: '.(string) $resultado['cliente_identificacion'].' - '.(string) $resultado['cliente_nombre']);
            $this->info('ID Cargue: '.(string) $resultado['id_cargue']);
            $this->info('Nro interno (authorization): '.(string) $resultado['nro_interno_objetivo']);
            $this->info('CODAGENCIA destino: '.(string) ($resultado['codagencia_objetivo'] ?? ''));
            $this->info('AGENCIA destino: '.(string) ($resultado['agencia_objetivo'] ?? ''));

            if ($dryRun) {
                $this->line('');
                $this->info('Previsualizacion por tabla:');
                foreach ($resultado['preview'] as $tabla => $detalle) {
                    $this->line("- {$tabla}: ".json_encode($detalle, JSON_UNESCAPED_UNICODE));
                }
                $this->warn('Dry run: no se realizaron escrituras.');

                return self::SUCCESS;
            }

            $this->info('Comprobante ID: '.(string) $resultado['comprobante_id']);
            $this->info('Comprobante: '.(string) $resultado['comprobante']);
            $this->info('Estado final detalle: '.(string) $resultado['estado_final']);
            $this->info('Regularizacion completada.');

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Fallo en regularizacion: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
