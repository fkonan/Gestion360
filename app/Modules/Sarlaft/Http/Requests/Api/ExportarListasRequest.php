<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ExportarListasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $tipoDescarga = strtolower(trim((string) $this->query('tipo_descarga', 'completa')));

        $this->merge([
            'tipo_descarga' => $tipoDescarga,
            'fecha_desde' => $this->query('fecha_desde'),
            'page' => $this->query('page', 1),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tipo_descarga' => 'required|string|in:completa,novedades',
            'fecha_desde' => 'required_if:tipo_descarga,novedades|nullable|date_format:Y-m-d',
            'page' => 'nullable|integer|min:1',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tipo_descarga.in' => 'El tipo de descarga debe ser completa o novedades.',
            'fecha_desde.required_if' => 'El campo fecha_desde es obligatorio para novedades. Formato: YYYY-MM-DD',
            'fecha_desde.date_format' => 'El campo fecha_desde debe tener el formato YYYY-MM-DD.',
        ];
    }
}
