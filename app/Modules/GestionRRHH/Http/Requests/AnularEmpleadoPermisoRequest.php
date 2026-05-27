<?php

namespace App\Modules\GestionRRHH\Http\Requests;

use App\Modules\GestionRRHH\Services\Novedades\Permisos\EmpleadoPermisoService;
use Illuminate\Foundation\Http\FormRequest;

class AnularEmpleadoPermisoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return EmpleadoPermisoService::reglasAnulacion();
    }

    public function messages(): array
    {
        return EmpleadoPermisoService::mensajesAnulacion();
    }
}

