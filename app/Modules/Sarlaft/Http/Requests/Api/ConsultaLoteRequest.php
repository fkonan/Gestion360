<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ConsultaLoteRequest extends FormRequest
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
            'registros' => 'required|array|min:1|max:100',
            'registros.*.tipo_documento' => 'required|string|in:CC,NIT,CE,PA',
            'registros.*.numero_documento' => 'required|string|max:50',
            'registros.*.nombre' => 'nullable|string|max:300',
        ];
    }
}
