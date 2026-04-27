<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class DesbloquearRequest extends FormRequest
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
            'justificacion_desbloqueo' => 'required|string|max:2000',
            'archivo_soporte' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'archivo_soporte.mimes' => 'El archivo debe ser PDF, imagen (JPG/PNG) o documento Word.',
            'archivo_soporte.max' => 'El archivo no puede superar los 10 MB.',
        ];
    }
}
