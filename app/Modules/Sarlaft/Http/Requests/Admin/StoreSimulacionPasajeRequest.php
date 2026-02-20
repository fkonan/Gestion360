<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreSimulacionPasajeRequest extends FormRequest
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
            'fecha_viaje' => 'required|date',
            'tipo_documento' => 'required|string|max:20',
            'documento' => 'required|string|max:50',
            'nombres' => 'required|string|max:150',
            'apellidos' => 'required|string|max:150',
            'direccion' => 'required|string|max:250',
            'telefono' => 'required|string|max:30',
            'correo' => 'required|email|max:150',
        ];
    }
}
