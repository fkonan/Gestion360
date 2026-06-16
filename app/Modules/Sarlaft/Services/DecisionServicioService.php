<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Services;

use App\Modules\Sarlaft\Models\Alerta;
use App\Modules\Sarlaft\Models\IntentoOperacion;
use App\Modules\Sarlaft\Models\ListaNegraInterna;
use App\Modules\Sarlaft\Models\RegistroLista;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class DecisionServicioService
{
    /**
     * Resultado de aplicar una decision de "permitir servicio".
     */
    public const NIVEL_VINCULANTE = 'vinculante';

    public const NIVEL_RESTRICTIVA = 'restrictiva';

    /**
     * Estados de alerta que se consideran abiertos (pendientes de decision).
     */
    private const ESTADOS_ABIERTOS = ['pendiente', 'en_revision'];

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
            // Remocion/inactivacion en lista: es por documento (la persona deja de
            // bloquearse en todos los escenarios; la lista no distingue escenario).
            $afectados = $nivel === self::NIVEL_VINCULANTE
                ? $this->removerVinculantes($documento)
                : $this->retirarRestrictivas($documento, $motivo, $evidencia, $userId);

            // La marca de decision se propaga a TODAS las alertas abiertas del mismo
            // documento Y mismo escenario (tipo_operacion), no solo a la actual.
            $this->marcarAlertasDelEscenario($alerta, 'permitido', $motivo, $evidencia, $userId);

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
        DB::connection('mysql-sarlaft')->transaction(function () use ($alerta, $motivo, $evidencia, $userId): void {
            $this->marcarAlertasDelEscenario($alerta, 'bloqueado', $motivo, $evidencia, $userId);
        });
    }

    /**
     * Marca con la decision (permitido/bloqueado) TODAS las alertas abiertas del
     * mismo documento y mismo escenario (tipo_operacion del intento) que la alerta
     * dada, incluida ella. Asi una sola decision aplica a las repeticiones del
     * mismo caso, sin afectar otros escenarios del mismo documento.
     *
     * @param  array<int, array<string, mixed>>  $evidencia
     */
    private function marcarAlertasDelEscenario(Alerta $alerta, string $decision, string $motivo, array $evidencia, int $userId): int
    {
        $momento = now();
        $evidenciaFinal = $evidencia !== [] ? $evidencia : null;

        return $this->queryAlertasDelEscenario($alerta)
            ->whereIn('estado', self::ESTADOS_ABIERTOS)
            ->get()
            ->each(function (Alerta $hermana) use ($decision, $motivo, $evidenciaFinal, $userId, $momento): void {
                $hermana->update([
                    'estado' => 'atendida',
                    'decision_servicio' => $decision,
                    'decision_at' => $momento,
                    'notas' => $motivo,
                    'atendida_por' => $userId,
                    'fecha_atencion' => $momento,
                    'evidencias' => $evidenciaFinal ?? $hermana->evidencias,
                ]);
            })
            ->count();
    }

    /**
     * Cuenta cuantas alertas abiertas hay del mismo documento + escenario que la
     * dada (incluida ella). Util para la UI (saber cuantas se afectarian).
     */
    public function contarAlertasDelEscenario(Alerta $alerta): int
    {
        return $this->queryAlertasDelEscenario($alerta)
            ->whereIn('estado', self::ESTADOS_ABIERTOS)
            ->count();
    }

    /**
     * Query base: alertas del mismo documento cuyo intento tiene el mismo
     * tipo_operacion (escenario) que la alerta dada.
     */
    private function queryAlertasDelEscenario(Alerta $alerta): Builder
    {
        $documento = trim((string) $alerta->numero_documento);
        $tipoOperacion = $this->tipoOperacionDe($alerta);

        return Alerta::query()
            ->where('numero_documento', $documento)
            ->whereHas('intento', function (Builder $q) use ($tipoOperacion): void {
                $q->where('tipo_operacion', $tipoOperacion);
            });
    }

    /**
     * tipo_operacion (escenario) de la alerta, leido de su intento.
     */
    private function tipoOperacionDe(Alerta $alerta): ?string
    {
        $intento = $alerta->relationLoaded('intento')
            ? $alerta->intento
            : IntentoOperacion::find($alerta->intento_id);

        $tipo = $intento?->tipo_operacion;

        return $tipo !== null ? trim((string) $tipo) : null;
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
