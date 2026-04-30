<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Services;

use App\Modules\Sarlaft\Models\Alerta;

class GestionAlertaService
{
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
}
