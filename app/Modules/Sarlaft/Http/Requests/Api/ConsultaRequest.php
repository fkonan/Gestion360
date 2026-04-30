<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ConsultaRequest extends FormRequest
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
            'tipo_documento' => 'required|string|in:CC,NIT,CE,PA',
            'numero_documento' => 'required|string|max:50',
            'nombre' => 'nullable|string|max:300',
        ];
    }
}
