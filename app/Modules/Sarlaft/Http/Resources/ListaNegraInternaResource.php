<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ListaNegraInternaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'origen' => 'interna',
            'tipo_entidad' => $this->tipo_entidad,
            'tipo_documento' => $this->tipo_documento,
            'numero_documento' => $this->numero_documento,
            'nombres' => $this->nombres,
            'motivo' => $this->motivo,
            'estado' => $this->estado,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
