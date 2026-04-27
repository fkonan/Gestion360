<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListaVinculanteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nombre' => [
                'required',
                'string',
                'max:100',
                Rule::unique('mysql-sarlaft.sarlaft_listas_vinculantes', 'nombre'),
            ],
            'tipo' => 'required|string|in:vinculante,recomendada,interna',
            'url_fuente' => 'nullable|url|max:500',
            'frecuencia_sync' => 'nullable|string|max:50',
            'activa' => 'nullable|boolean',
        ];
    }
}
