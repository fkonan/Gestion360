<?php

namespace App\Modules\RadFact\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDistribucionesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'distribuciones' => 'required|array|min:1',
            'distribuciones.*.area_id' => 'required|exists:rad_fact_areas,id',
            'distribuciones.*.porcentaje' => 'required|numeric|min:0|max:100',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'distribuciones.required' => 'Debe agregar al menos una distribución.',
            'distribuciones.min' => 'Debe agregar al menos una distribución.',
            'distribuciones.*.area_id.required' => 'Debe seleccionar un área para cada distribución.',
            'distribuciones.*.area_id.exists' => 'El área seleccionada no existe.',
            'distribuciones.*.porcentaje.required' => 'Debe especificar el porcentaje para cada distribución.',
            'distribuciones.*.porcentaje.min' => 'El porcentaje debe ser mayor o igual a 0.',
            'distribuciones.*.porcentaje.max' => 'El porcentaje no puede ser mayor a 100.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validar que los porcentajes sumen 100
            if ($this->has('distribuciones')) {
                $totalPorcentaje = collect($this->distribuciones)->sum('porcentaje');

                if (abs($totalPorcentaje - 100) > 0.01) {
                    $validator->errors()->add(
                        'distribuciones',
                        "Los porcentajes deben sumar 100%. Actualmente suman: {$totalPorcentaje}%"
                    );
                }
            }
        });
    }
}
