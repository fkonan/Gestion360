<?php

namespace App\Modules\GestionRRHH\Http\Requests;

use App\Modules\GestionRRHH\Services\Novedades\Vacaciones\EmpleadoVacacionDocumentoService;
use App\Modules\GestionRRHH\Services\Novedades\Vacaciones\EmpleadoVacacionService;
use Illuminate\Foundation\Http\FormRequest;

class StoreEmpleadoVacacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return array_merge(
            EmpleadoVacacionService::reglasCreacion(),
            EmpleadoVacacionDocumentoService::reglasDocumento()
        );
    }

    public function messages(): array
    {
        return array_merge(
            EmpleadoVacacionService::mensajesCreacion(),
            EmpleadoVacacionDocumentoService::mensajesDocumento()
        );
    }
}

