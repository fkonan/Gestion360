<?php

namespace App\Modules\RadFact\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRadicacionRequest extends FormRequest
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
            // Datos de radicación
            'proveedor_id' => 'required|exists:rad_fact_proveedores,id',
            'num_factura' => 'required|string|max:50',
            'num_contrato' => 'nullable|string|max:50',
            'numero_pagos' => 'required|integer|min:1|max:999',
            'fecha_radicacion' => 'required|date',
            'fecha_vencimiento' => 'required|date|after_or_equal:fecha_radicacion',
            'necesita_visto_bueno' => 'nullable|boolean',
            'descripcion' => 'nullable|string|max:1000',
            'valor' => 'required|numeric|min:0|max:999999999999.99',
            'pdf' => 'nullable|file|mimes:pdf|max:10240', // 10MB
            'observacion' => 'nullable|string|max:1000',

            // Distribuciones
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
            'proveedor_id.required' => 'Debe seleccionar un proveedor.',
            'proveedor_id.exists' => 'El proveedor seleccionado no existe.',
            'num_factura.required' => 'El número de factura es obligatorio.',
            'numero_pagos.min' => 'Debe haber al menos 1 pago.',
            'fecha_radicacion.required' => 'La fecha de radicación es obligatoria.',
            'fecha_vencimiento.required' => 'La fecha de vencimiento es obligatoria.',
            'fecha_vencimiento.after_or_equal' => 'La fecha de vencimiento debe ser igual o posterior a la fecha de radicación.',
            'valor.required' => 'El valor de la factura es obligatorio.',
            'valor.min' => 'El valor debe ser mayor o igual a 0.',
            'pdf.mimes' => 'El archivo debe ser un PDF.',
            'pdf.max' => 'El archivo PDF no puede superar los 10MB.',

            // Distribuciones
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
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'proveedor_id' => 'proveedor',
            'num_factura' => 'número de factura',
            'num_contrato' => 'número de contrato',
            'numero_pagos' => 'número de pagos',
            'fecha_radicacion' => 'fecha de radicación',
            'fecha_vencimiento' => 'fecha de vencimiento',
            'necesita_visto_bueno' => 'requiere visto bueno',
            'descripcion' => 'descripción',
            'valor' => 'valor',
            'pdf' => 'archivo PDF',
            'observacion' => 'observación',
            'distribuciones' => 'distribuciones',
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

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'necesita_visto_bueno' => $this->boolean('necesita_visto_bueno'),
        ]);
    }
}
