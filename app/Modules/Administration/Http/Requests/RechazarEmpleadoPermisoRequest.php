<?php

namespace App\Modules\Administration\Http\Requests;

use App\Modules\Administration\Services\EmpleadoPermisoService;
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

