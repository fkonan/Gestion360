<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Services;

use App\Modules\Sarlaft\Models\Alerta;
use Illuminate\Support\Facades\DB;

class GestionAlertaService
{
    /**
     * Estados que se consideran "abiertos" para el cierre masivo por documento.
     */
    private const ESTADOS_ABIERTOS = ['pendiente', 'en_revision'];

    /**
     * @param  array<string, mixed>  $datos
     */
    public function atender(
        Alerta $alerta,
        array $datos,
        ?int $userId,
        bool $esAutomatica = false,
    ): void {
        $momentoAtencion = now();
        $payload = [
            'estado' => (string) $datos['estado'],
            'notas' => $datos['notas'] ?? $alerta->notas,
            'atendida_por' => $userId ?? $alerta->atendida_por,
            'fecha_atencion' => $momentoAtencion,
        ];

        $evidenciasActuales = is_array($alerta->evidencias) ? $alerta->evidencias : [];
        $evidenciasNuevas = isset($datos['evidencias']) && is_array($datos['evidencias'])
            ? array_values($datos['evidencias'])
            : [];

        if ($evidenciasNuevas !== []) {
            $payload['evidencias'] = array_merge($evidenciasActuales, $evidenciasNuevas);
        }

        if ($esAutomatica) {
            $contextoOperacion = is_array($alerta->contexto_operacion) ? $alerta->contexto_operacion : [];
            $contextoOperacion['escalada_automatica'] = true;
            $contextoOperacion['escalada_automatica_at'] = $momentoAtencion->toIso8601String();

            $payload['escalada_automatica'] = true;
            $payload['escalada_automatica_at'] = $momentoAtencion;
            $payload['contexto_operacion'] = $contextoOperacion;
        }

        $alerta->update($payload);
        $alerta->refresh();
    }

    /**
     * Atiende en bloque todas las alertas abiertas (pendiente/en_revision) del
     * mismo numero de documento que la alerta dada, incluida ella. Aplica estado
     * y notas; NO propaga evidencias. Devuelve la cantidad de alertas afectadas.
     *
     * @param  array<string, mixed>  $datos
     */
    public function atenderRelacionadas(Alerta $alerta, array $datos, ?int $userId): int
    {
        $estado = (string) $datos['estado'];
        $notas = $datos['notas'] ?? null;
        $momentoAtencion = now();

        return DB::connection('mysql-sarlaft')->transaction(function () use ($alerta, $estado, $notas, $userId, $momentoAtencion): int {
            return Alerta::query()
                ->where('numero_documento', $alerta->numero_documento)
                ->whereIn('estado', self::ESTADOS_ABIERTOS)
                ->update([
                    'estado' => $estado,
                    'notas' => $notas,
                    'atendida_por' => $userId,
                    'fecha_atencion' => $momentoAtencion,
                ]);
        });
    }

    /**
     * Cuenta cuantas alertas abiertas (pendiente/en_revision) existen para el
     * mismo numero de documento, incluida la actual.
     */
    public function contarRelacionadasAbiertas(Alerta $alerta): int
    {
        return Alerta::query()
            ->where('numero_documento', $alerta->numero_documento)
            ->whereIn('estado', self::ESTADOS_ABIERTOS)
            ->count();
    }
}
