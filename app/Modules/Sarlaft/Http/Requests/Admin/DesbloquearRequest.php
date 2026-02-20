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
            'documentos_soporte' => 'nullable|array',
            'documentos_soporte.*' => 'string|max:500',
        ];
    }
}
