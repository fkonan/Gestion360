<?php

namespace App\Modules\GestionRRHH\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmpleadoIncapacidadSeguimientoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'observacion_seguimiento' => 'required|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'observacion_seguimiento.required' => 'Debes registrar una observacion para el seguimiento.',
            'observacion_seguimiento.max' => 'La observacion de seguimiento no puede exceder 1000 caracteres.',
        ];
    }
}
