<?php

namespace App\Modules\GestionRRHH\Http\Requests;

use App\Modules\GestionRRHH\Http\Requests\Concerns\ValidaAdjuntosEmpleadoPermiso;
use App\Modules\GestionRRHH\Services\Novedades\Permisos\EmpleadoPermisoDocumentoService;
use App\Modules\GestionRRHH\Services\Novedades\Permisos\EmpleadoPermisoService;
use Illuminate\Foundation\Http\FormRequest;

class StoreEmpleadoPermisoRequest extends FormRequest
{
    use ValidaAdjuntosEmpleadoPermiso;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return array_merge(
            EmpleadoPermisoService::reglasCreacion(),
            EmpleadoPermisoDocumentoService::reglasAdjuntos()
        );
    }

    public function messages(): array
    {
        return array_merge(
            EmpleadoPermisoService::mensajesCreacion(),
            EmpleadoPermisoDocumentoService::mensajesAdjuntos()
        );
    }

    public function withValidator($validator): void
    {
        $this->agregarValidacionAdjuntos($validator);
    }
}

