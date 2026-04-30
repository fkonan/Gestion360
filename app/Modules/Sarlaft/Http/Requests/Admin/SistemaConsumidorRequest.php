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
            'nombre'                 => $isUpdate ? 'sometimes|string|max:100' : 'required|string|max:100',
            'codigo'                 => $isUpdate ? ['sometimes', 'string', 'max:50', $codigoUnique] : ['required', 'string', 'max:50', $codigoUnique],
            'limite_requests_minuto' => 'required|integer|min:1|max:10000',
            'pull_endpoint'          => 'nullable|url|max:500',
            'pull_token'             => 'nullable|string|max:255',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'pull_endpoint.url' => 'El endpoint Pull debe ser una URL válida (ej: https://api.empresa.com/intentos).',
        ];
    }
}
