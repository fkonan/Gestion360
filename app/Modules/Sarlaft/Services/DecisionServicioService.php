<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Services;

use App\Modules\Sarlaft\Models\Alerta;
use App\Modules\Sarlaft\Models\ListaNegraInterna;
use App\Modules\Sarlaft\Models\RegistroLista;
use Illuminate\Support\Facades\DB;

class DecisionServicioService
{
    /**
     * Resultado de aplicar una decision de "permitir servicio".
     */
    public const NIVEL_VINCULANTE = 'vinculante';

    public const NIVEL_RESTRICTIVA = 'restrictiva';

    public function __construct(
        private readonly NovedadExportacionService $novedadExportacionService,
    ) {}

    /**
     * Permite el servicio a la persona de la alerta: remueve/inactiva TODOS los
     * registros activos de ese documento en la lista correspondiente segun el
     * tipo de la alerta. Como la API a externos solo entrega registros activos,
     * la persona deja de bloquearse en la proxima descarga.
     *
     * Devuelve cuantos registros de lista fueron afectados.
     *
     * @param  array<int, array<string, mixed>>  $evidencia
     */
    public function permitirServicio(Alerta $alerta, string $motivo, array $evidencia, int $userId): int
    {
        $documento = trim((string) $alerta->numero_documento);

        if ($documento === '') {
            return 0;
        }

        $nivel = strtolower(trim((string) $alerta->nivel_riesgo));

        return DB::connection('mysql-sarlaft')->transaction(function () use ($alerta, $nivel, $documento, $motivo, $evidencia, $userId): int {
            $afectados = $nivel === self::NIVEL_VINCULANTE
                ? $this->removerVinculantes($documento)
                : $this->retirarRestrictivas($documento, $motivo, $evidencia, $userId);

            // La alerta queda atendida con la decision de permitir servicio documentada.
            $alerta->update([
                'estado' => 'atendida',
                'decision_servicio' => 'permitido',
                'decision_at' => now(),
                'notas' => $motivo,
                'atendida_por' => $userId,
                'fecha_atencion' => now(),
                'evidencias' => $evidencia !== [] ? $evidencia : $alerta->evidencias,
            ]);

            return $afectados;
        });
    }

    /**
     * Registra la decision explicita de MANTENER el bloqueo de servicio. No toca
     * las listas (la persona sigue bloqueada por defecto); solo documenta la
     * decision y el motivo para el reporte de decisiones.
     *
     * @param  array<int, array<string, mixed>>  $evidencia
     */
    public function mantenerBloqueo(Alerta $alerta, string $motivo, array $evidencia, int $userId): void
    {
        $alerta->update([
            'estado' => 'atendida',
            'decision_servicio' => 'bloqueado',
            'decision_at' => now(),
            'notas' => $motivo,
            'atendida_por' => $userId,
            'fecha_atencion' => now(),
            'evidencias' => $evidencia !== [] ? $evidencia : $alerta->evidencias,
        ]);
    }

    /**
     * Marca como 'removido' todos los registros vinculantes activos del documento
     * y registra la novedad de 'salida' para que la exportacion por novedades
     * propague el delta a los sistemas externos.
     */
    private function removerVinculantes(string $documento): int
    {
        $registros = RegistroLista::query()
            ->with('lista')
            ->where('identificacion', $documento)
            ->where('estado', 'activo')
            ->get();

        foreach ($registros as $registro) {
            $registro->update([
                'estado' => 'removido',
                'novedad' => 'salida',
            ]);

            $this->novedadExportacionService->registrarNovedadVinculante(
                registro: $registro,
                tipoNovedad: 'salida',
                nombreLista: (string) ($registro->lista?->nombre ?? 'Lista vinculante'),
            );
        }

        return $registros->count();
    }

    /**
     * Inactiva (retira) todos los registros restrictivos activos del documento,
     * documentando el motivo, la evidencia y el responsable del retiro, y
     * registra la novedad de 'salida' para la exportacion por novedades.
     *
     * @param  array<int, array<string, mixed>>  $evidencia
     */
    private function retirarRestrictivas(string $documento, string $motivo, array $evidencia, int $userId): int
    {
        $registros = ListaNegraInterna::query()
            ->where('numero_documento', $documento)
            ->where('estado', 'activo')
            ->get();

        foreach ($registros as $registro) {
            $registro->update([
                'estado' => 'inactivo',
                'motivo_retiro' => $motivo,
                'evidencia_retiro' => $evidencia !== [] ? $evidencia : null,
                'retirado_por' => $userId,
                'retirado_at' => now(),
            ]);

            $this->novedadExportacionService->registrarNovedadInterna(
                registro: $registro,
                tipoNovedad: 'salida',
            );
        }

        return $registros->count();
    }
}
