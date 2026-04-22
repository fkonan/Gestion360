<?php

namespace App\Modules\RadFact\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProveedorRequest extends FormRequest
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
        $proveedorId = $this->route('proveedor')->id ?? null;

        return [
            'tipo_documento' => 'required|string|max:20',
            'documento' => "required|string|max:50|unique:rad_fact_proveedores,documento,{$proveedorId}",
            'nombres' => 'nullable|string|max:150',
            'apellidos' => 'nullable|string|max:150',
            'razon_social' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:50',
            'correo' => 'nullable|email|max:150',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'tipo_documento.required' => 'El tipo de documento es obligatorio.',
            'documento.required' => 'El número de documento es obligatorio.',
            'documento.unique' => 'Este documento ya está registrado.',
            'correo.email' => 'El correo debe ser una dirección válida.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'tipo_documento' => 'tipo de documento',
            'documento' => 'número de documento',
            'nombres' => 'nombres',
            'apellidos' => 'apellidos',
            'razon_social' => 'razón social',
            'telefono' => 'teléfono',
            'correo' => 'correo electrónico',
        ];
    }
}
