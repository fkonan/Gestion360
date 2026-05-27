<?php

namespace App\Modules\GestionRRHH\Http\Requests;

use App\Modules\GestionRRHH\Services\Novedades\PermisosPermanentes\EmpleadoPermisoPermanenteDocumentoService;
use App\Modules\GestionRRHH\Services\Novedades\PermisosPermanentes\EmpleadoPermisoPermanenteService;
use Illuminate\Foundation\Http\FormRequest;

class StoreEmpleadoPermisoPermanenteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return array_merge(
            EmpleadoPermisoPermanenteService::reglasCreacion(),
            EmpleadoPermisoPermanenteDocumentoService::reglasDocumentos()
        );
    }

    public function messages(): array
    {
        return array_merge(
            EmpleadoPermisoPermanenteService::mensajesCreacion(),
            EmpleadoPermisoPermanenteDocumentoService::mensajesDocumentos()
        );
    }
}
