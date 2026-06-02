<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class PermitirServicioRequest extends FormRequest
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
            'motivo' => 'required|string|max:2000',
            'evidencias' => 'nullable|array|max:5',
            'evidencias.*' => 'file|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx|max:10240',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'motivo.required' => 'Debe indicar el motivo para permitir el servicio.',
            'motivo.max' => 'El motivo no debe superar 2000 caracteres.',
            'evidencias.max' => 'Solo puede adjuntar hasta 5 soportes.',
            'evidencias.*.file' => 'Cada soporte debe ser un archivo valido.',
            'evidencias.*.mimes' => 'Los soportes deben ser PDF, imagen o documentos Office.',
            'evidencias.*.max' => 'Cada soporte no debe superar los 10 MB.',
        ];
    }
}
