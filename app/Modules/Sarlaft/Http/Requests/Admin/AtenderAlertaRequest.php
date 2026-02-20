<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AtenderAlertaRequest extends FormRequest
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
            'estado' => 'required|string|in:pendiente,en_revision,atendida,descartada',
            'decision_servicio' => 'required|string|in:sin_decision,bloquear,permitir_una_operacion,permitir_permanente',
            'notas' => 'nullable|string|max:2000',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'estado.required' => 'El estado de la alerta es obligatorio.',
            'estado.in' => 'El estado seleccionado no es valido.',
            'decision_servicio.required' => 'Debes seleccionar una decision de servicio.',
            'decision_servicio.in' => 'La decision de servicio seleccionada no es valida.',
            'notas.max' => 'Las notas no deben superar 2000 caracteres.',
        ];
    }
}
