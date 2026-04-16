<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AlertaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $evidencias = collect(is_array($this->evidencias) ? $this->evidencias : [])
            ->filter(static fn (mixed $evidencia): bool => is_array($evidencia))
            ->map(static fn (array $evidencia): array => [
                'id' => $evidencia['id'] ?? null,
                'original_name' => $evidencia['original_name'] ?? null,
                'mime_type' => $evidencia['mime_type'] ?? null,
                'size_bytes' => $evidencia['size_bytes'] ?? null,
                'uploaded_by' => $evidencia['uploaded_by'] ?? null,
                'uploaded_at' => $evidencia['uploaded_at'] ?? null,
            ])
            ->values()
            ->all();

        return [
            'id' => $this->id,
            'consulta_id' => $this->consulta_id,
            'tipo' => $this->tipo,
            'nivel_riesgo' => $this->nivel_riesgo,
            'estado' => $this->estado,
            'tipo_documento' => $this->tipo_documento,
            'numero_documento' => $this->numero_documento,
            'decision_servicio' => $this->decision_servicio,
            'decision_activa' => $this->decision_activa,
            'decision_consumida_at' => $this->decision_consumida_at?->toIso8601String(),
            'decision_consumida_consulta_id' => $this->decision_consumida_consulta_id,
            'escalada_automatica' => $this->escalada_automatica,
            'escalada_automatica_at' => $this->escalada_automatica_at?->toIso8601String(),
            'datos_persona' => $this->datos_persona,
            'listas_coincidentes' => $this->listas_coincidentes,
            'contexto_operacion' => $this->contexto_operacion,
            'evidencias' => $evidencias,
            'atendida_por' => $this->atendida_por,
            'fecha_atencion' => $this->fecha_atencion?->toIso8601String(),
            'notas' => $this->notas,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
