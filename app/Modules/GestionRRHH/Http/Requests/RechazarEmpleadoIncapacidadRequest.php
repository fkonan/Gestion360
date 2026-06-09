<?php

namespace App\Modules\GestionRRHH\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RechazarEmpleadoIncapacidadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'motivo_rechazo' => 'required|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'motivo_rechazo.required' => 'Debes indicar el motivo del rechazo.',
            'motivo_rechazo.max' => 'El motivo del rechazo no puede exceder 255 caracteres.',
        ];
    }
}
