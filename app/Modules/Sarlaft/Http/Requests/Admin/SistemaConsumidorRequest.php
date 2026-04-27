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
        $codigoUnique = Rule::unique('mysql-sarlaft.sarlaft_sistemas_consumidores', 'codigo');

        if ($this->route('sistema_consumidor')) {
            $codigoUnique->ignore($this->route('sistema_consumidor'));
        }

        return [
            'nombre' => 'required|string|max:100',
            'codigo' => ['required', 'string', 'max:50', $codigoUnique],
            'limite_requests_minuto' => 'required|integer|min:1|max:10000',
        ];
    }
}
