<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreSimulacionRemesaRequest extends FormRequest
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
            'ciudad_origen_id' => 'required|integer|exists:mysql-gestion-admin._municipios,IdMunicipio',
            'ciudad_destino_id' => 'required|integer|different:ciudad_origen_id|exists:mysql-gestion-admin._municipios,IdMunicipio',
            'fecha_envio' => 'required|date',
            'tipo_documento' => 'required|string|max:20',
            'documento_remitente' => 'required|string|max:50',
            'nombres_remitente' => 'required|string|max:150',
            'apellidos_remitente' => 'required|string|max:150',
            'telefono_remitente' => 'required|string|max:30',
            'nombre_destinatario' => 'required|string|max:300',
            'documento_destinatario' => 'required|string|max:50',
            'monto' => 'required|numeric|min:0.01|max:999999999999.99',
            'concepto' => 'required|string|max:500',
        ];
    }
}
