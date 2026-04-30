<?php

namespace App\Modules\Administration\Http\Requests;

use App\Modules\Administration\Services\EmpleadoPermisoService;
use Illuminate\Foundation\Http\FormRequest;

class StoreEmpleadoPermisoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return EmpleadoPermisoService::reglasCreacion();
    }

    public function messages(): array
    {
        return EmpleadoPermisoService::mensajesCreacion();
    }
}

