<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SistemaConsumidorRequest extends FormRequest
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
        $isUpdate = $this->isMethod('PATCH') || $this->isMethod('PUT');

        $codigoUnique = Rule::unique('mysql-sarlaft.sarlaft_sistemas_consumidores', 'codigo');

        if ($this->route('sistema_consumidor')) {
            $codigoUnique->ignore($this->route('sistema_consumidor'));
        }

        return [
            'nombre' => $isUpdate ? 'sometimes|string|max:100' : 'required|string|max:100',
            'codigo' => $isUpdate ? ['sometimes', 'string', 'max:50', $codigoUnique] : ['required', 'string', 'max:50', $codigoUnique],
            'limite_requests_minuto' => 'required|integer|min:1|max:10000',
            'modo_integracion' => $isUpdate ? 'sometimes|in:push,pull,db' : 'required|in:push,pull,db',
            'pull_endpoint' => 'nullable|url|max:500',
            'pull_token' => 'nullable|string|max:255',
            'db_conexion' => 'nullable|string|max:100|required_if:modo_integracion,db',
            'db_tabla' => 'nullable|string|max:150|required_if:modo_integracion,db',
            'db_filtro_sistema_origen' => 'nullable|string|max:100',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'pull_endpoint.url' => 'El endpoint Pull debe ser una URL válida (ej: https://api.empresa.com/intentos).',
            'modo_integracion.required' => 'Debe seleccionar un modo de integración.',
            'modo_integracion.in' => 'El modo de integración debe ser Push, Pull o Lectura de BD.',
            'db_conexion.required_if' => 'La conexión de BD es obligatoria cuando el modo es Lectura directa de BD.',
            'db_tabla.required_if' => 'La tabla de BD es obligatoria cuando el modo es Lectura directa de BD.',
        ];
    }
}
