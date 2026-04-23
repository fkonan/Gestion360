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
            'punto_de_control' => $this->query('punto_de_control', 0),
            'tamano_lote' => $this->query('tamano_lote', 1000),
            'lista_id' => $this->query('lista_id'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tipo_descarga' => 'required|string|in:completa,novedades',
            'punto_de_control' => 'required_if:tipo_descarga,novedades|integer|min:0',
            'tamano_lote' => 'required_if:tipo_descarga,novedades|integer|min:1|max:5000',
            'lista_id' => 'nullable|integer|exists:mysql-sarlaft.sarlaft_listas_vinculantes,id',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tipo_descarga.in' => 'El tipo de descarga debe ser completa o novedades.',
            'punto_de_control.required_if' => 'El punto_de_control es obligatorio para descargar novedades.',
            'tamano_lote.required_if' => 'El tamano_lote es obligatorio para descargar novedades.',
        ];
    }
}
