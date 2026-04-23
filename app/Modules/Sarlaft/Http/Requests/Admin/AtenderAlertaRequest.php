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
            'notas' => 'nullable|string|max:2000',
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
            'estado.required' => 'El estado de la alerta es obligatorio.',
            'estado.in' => 'El estado seleccionado no es valido.',
            'notas.max' => 'Las notas no deben superar 2000 caracteres.',
            'evidencias.array' => 'Las evidencias deben enviarse como una lista de archivos.',
            'evidencias.max' => 'Solo puedes adjuntar hasta 5 evidencias por actualizacion.',
            'evidencias.*.file' => 'Cada evidencia debe ser un archivo valido.',
            'evidencias.*.mimes' => 'Las evidencias deben estar en formato PDF, imagen o documento Office.',
            'evidencias.*.max' => 'Cada evidencia no debe superar los 10 MB.',
        ];
    }
}
