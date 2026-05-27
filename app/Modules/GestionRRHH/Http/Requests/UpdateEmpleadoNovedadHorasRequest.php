<?php

namespace App\Modules\GestionRRHH\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmpleadoNovedadHorasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fin' => 'nullable|date_format:H:i',
        ];
    }

    public function messages(): array
    {
        return [
            'hora_inicio.required' => 'La hora de inicio es obligatoria.',
            'hora_inicio.date_format' => 'La hora de inicio no tiene formato valido.',
            'hora_fin.date_format' => 'La hora de fin no tiene formato valido.',
        ];
    }
}
