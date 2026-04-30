<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class IntentoOperacionRequest extends FormRequest
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
            'tipo_documento' => 'required|string|max:20',
            'numero_documento' => 'required|string|max:50',
            'nombre' => 'nullable|string|max:300',
            'tipo_lista' => 'required|string|max:100',
            'lista_nombre' => 'required|string|max:150',
            'tipo_operacion' => 'required|string|max:100',
            'referencia' => 'nullable|string|max:100',
            'monto' => 'nullable|numeric|min:0',
            'descripcion' => 'nullable|string|max:500',
            'contexto' => 'nullable|array',
            'created_at' => 'nullable|date',
            'sistema_origen' => 'nullable|string|max:100',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tipo_documento.required' => 'El tipo_documento es obligatorio.',
            'numero_documento.required' => 'El numero_documento es obligatorio.',
            'tipo_lista.required' => 'El tipo_lista es obligatorio.',
            'lista_nombre.required' => 'El lista_nombre es obligatorio.',
            'tipo_operacion.required' => 'El tipo_operacion es obligatorio.',
        ];
    }
}
