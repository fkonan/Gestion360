<?php

namespace App\Modules\RadFact\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RechazarRequest extends FormRequest
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
            'observacion' => 'required|string|min:10|max:1000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'observacion.required' => 'Debe proporcionar una observación al rechazar.',
            'observacion.min' => 'La observación debe tener al menos 10 caracteres.',
            'observacion.max' => 'La observación no puede superar los 1000 caracteres.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'observacion' => 'observación',
        ];
    }
}
