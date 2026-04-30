<?php

namespace App\Modules\Administration\Http\Requests;

use App\Modules\Administration\Services\EmpleadoPermisoService;
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

