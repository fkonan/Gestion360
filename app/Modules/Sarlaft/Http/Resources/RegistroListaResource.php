<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RegistroListaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'origen' => 'vinculante',
            'lista' => $this->whenLoaded('lista', fn () => $this->lista->nombre),
            'tipo_entidad' => $this->tipo_entidad,
            'identificacion' => $this->identificacion,
            'tipo_identificacion' => $this->tipo_identificacion,
            'nombres' => $this->nombres,
            'alias' => $this->alias,
            'fecha_nacimiento' => $this->fecha_nacimiento?->format('Y-m-d'),
            'pais' => $this->pais,
            'motivo' => $this->motivo,
            'fecha_inclusion' => $this->fecha_inclusion?->format('Y-m-d'),
            'estado' => $this->estado,
            'novedad' => $this->novedad,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
