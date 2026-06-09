<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ConsultarListaRequest extends FormRequest
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
        ];
    }
}
