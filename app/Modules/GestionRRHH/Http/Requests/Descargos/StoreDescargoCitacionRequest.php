<?php

namespace App\Modules\GestionRRHH\Http\Requests\Descargos;

use Illuminate\Foundation\Http\FormRequest;

class StoreDescargoCitacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'documento_persona' => ['required', 'string', 'max:100'],
            'fecha_citacion' => ['required', 'date'],
            'observacion' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'documento_persona.required' => 'El documento de la persona es obligatorio.',
            'documento_persona.max' => 'El documento de la persona no debe superar 100 caracteres.',
            'fecha_citacion.required' => 'La fecha de citacion es obligatoria.',
            'fecha_citacion.date' => 'La fecha de citacion no es valida.',
        ];
    }
}
