<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListaNegraRequest extends FormRequest
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
        $uniqueRule = Rule::unique('mysql-sarlaft.sarlaft_lista_negra_interna', 'numero_documento')
            ->where('tipo_documento', $this->input('tipo_documento'));

        if ($this->route('lista_negra')) {
            $uniqueRule->ignore($this->route('lista_negra'));
        }

        return [
            'tipo_entidad' => 'required|string|in:persona,organizacion',
            'tipo_documento' => 'required|string|in:CC,NIT,CE,PA',
            'numero_documento' => ['required', 'string', 'max:50', $uniqueRule],
            'nombres' => 'required|string|max:500',
            'motivo' => 'required|string|max:2000',
            'estado' => 'sometimes|string|in:activo,inactivo',
        ];
    }
}
