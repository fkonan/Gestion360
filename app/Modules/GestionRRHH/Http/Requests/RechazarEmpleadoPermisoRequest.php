<?php

namespace App\Modules\GestionRRHH\Http\Requests;

use App\Modules\GestionRRHH\Services\Novedades\Permisos\EmpleadoPermisoService;
use Illuminate\Foundation\Http\FormRequest;

class RechazarEmpleadoPermisoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return EmpleadoPermisoService::reglasRechazo();
    }

    public function messages(): array
    {
        return EmpleadoPermisoService::mensajesRechazo();
    }
}

