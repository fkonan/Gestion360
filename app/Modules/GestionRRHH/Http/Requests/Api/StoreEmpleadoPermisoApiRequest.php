<?php

namespace App\Modules\GestionRRHH\Http\Requests\Api;

use App\Modules\GestionRRHH\Http\Requests\Concerns\ValidaAdjuntosEmpleadoPermiso;
use App\Modules\GestionRRHH\Services\Novedades\Permisos\EmpleadoPermisoDocumentoService;
use App\Modules\GestionRRHH\Services\Novedades\Permisos\EmpleadoPermisoService;
use Illuminate\Foundation\Http\FormRequest;

class StoreEmpleadoPermisoApiRequest extends FormRequest
{
    use ValidaAdjuntosEmpleadoPermiso;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = array_merge(
            EmpleadoPermisoService::reglasCreacion(),
            EmpleadoPermisoDocumentoService::reglasAdjuntos()
        );
        $rules['documento_actor'] = 'required|string|max:50';
        $rules['nombre_actor'] = 'nullable|string|max:200';
        $rules['documento_usuario'] = 'nullable|string|max:50';
        $rules['nombre_usuario'] = 'nullable|string|max:200';
        $rules['sistema_origen'] = 'nullable|string|max:120';
        $rules['ip_equipo'] = 'nullable|string|max:120';

        return $rules;
    }

    public function messages(): array
    {
        return array_merge(
            EmpleadoPermisoService::mensajesCreacion(),
            EmpleadoPermisoDocumentoService::mensajesAdjuntos(),
            [
                'documento_actor.required' => 'El campo documento_actor (quien radica) es obligatorio.',
            ]
        );
    }

    protected function prepareForValidation(): void
    {
        $claims = $this->attributes->get('api_jwt_claims', []);
        $claims = is_array($claims) ? $claims : [];
        $documentoActorToken = trim((string) ($claims['actor_documento'] ?? ''));

        $documentoActor = trim((string) $this->input('documento_actor', ''));
        $documentoRadica = trim((string) $this->input('documento_radica', ''));
        $documentoUsuario = trim((string) $this->input('documento_usuario', ''));
        $documentoPersona = trim((string) $this->input('documento_persona', ''));
        $identificacion = trim((string) $this->input('identificacion', ''));
        $nombreActor = trim((string) $this->input('nombre_actor', ''));
        $nombreUsuario = trim((string) $this->input('nombre_usuario', ''));

        $actor = $documentoActor !== ''
            ? $documentoActor
            : ($documentoUsuario !== '' ? $documentoUsuario : ($documentoRadica !== '' ? $documentoRadica : $documentoActorToken));
        $persona = $documentoPersona !== ''
            ? $documentoPersona
            : ($identificacion !== '' ? $identificacion : $actor);
        $nombre = $nombreActor !== '' ? $nombreActor : $nombreUsuario;

        $this->merge([
            'documento_actor' => $actor,
            'documento_usuario' => $actor,
            'identificacion' => $persona,
            'documento_persona' => $persona,
            'nombre_actor' => $nombre,
            'nombre_usuario' => $nombre,
        ]);
    }

    public function withValidator($validator): void
    {
        $this->agregarValidacionAdjuntos($validator);
    }
}

