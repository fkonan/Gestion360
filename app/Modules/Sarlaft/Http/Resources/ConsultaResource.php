<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConsultaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sistema_origen' => $this->sistema_origen,
            'tipo_documento' => $this->tipo_documento,
            'numero_documento' => $this->numero_documento,
            'nombre_consultado' => $this->nombre_consultado,
            'encontrado' => $this->encontrado,
            'presta_servicio' => $this->presta_servicio,
            'nivel_riesgo' => $this->nivel_riesgo,
            'coincidencias' => $this->coincidencias,
            'created_at' => $this->created_at?->toIso8601String(),
            'alertas' => AlertaResource::collection($this->whenLoaded('alertas')),
        ];
    }
}
