<?php

namespace App\Modules\RadFact\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAreaRequest extends FormRequest
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
            'area' => 'required|string|max:150',
            'responsable' => 'nullable|string|max:200',
            'correo' => 'required|email|max:150',
            'subgerencia' => 'nullable|boolean',
            'compras' => 'nullable|boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'area.required' => 'El nombre del área es obligatorio.',
            'correo.required' => 'El correo es obligatorio para enviar notificaciones.',
            'correo.email' => 'El correo debe ser una dirección válida.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'area' => 'área',
            'responsable' => 'responsable',
            'correo' => 'correo electrónico',
            'subgerencia' => 'subgerencia administrativa',
            'compras' => 'área de compras',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'subgerencia' => $this->boolean('subgerencia'),
            'compras' => $this->boolean('compras'),
        ]);
    }
}
