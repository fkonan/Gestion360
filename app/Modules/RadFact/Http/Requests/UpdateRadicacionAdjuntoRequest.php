<?php

namespace App\Modules\RadFact\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRadicacionAdjuntoRequest extends FormRequest
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
            'pdf' => 'required|file|mimes:pdf|max:10240',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'pdf.required' => 'Debe cargar un archivo PDF.',
            'pdf.file' => 'El adjunto debe ser un archivo válido.',
            'pdf.mimes' => 'El archivo debe estar en formato PDF.',
            'pdf.max' => 'El archivo PDF no puede superar los 10MB.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'pdf' => 'archivo PDF',
        ];
    }
}
