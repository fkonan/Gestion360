<?php

namespace App\Modules\Administration\Http\Requests\Api;

use App\Modules\Administration\Services\EmpleadoPermisoService;
use Illuminate\Foundation\Http\FormRequest;

class StoreEmpleadoPermisoApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = EmpleadoPermisoService::reglasCreacion();
        $rules['documento_usuario'] = 'required|string|max:50';
        $rules['nombre_usuario'] = 'nullable|string|max:200';
        $rules['sistema_origen'] = 'nullable|string|max:120';
        $rules['ip_equipo'] = 'nullable|string|max:120';

        return $rules;
    }

    public function messages(): array
    {
        return array_merge(
            EmpleadoPermisoService::mensajesCreacion(),
            [
                'documento_usuario.required' => 'El documento del usuario solicitante es obligatorio.',
            ]
        );
    }
}
