<?php

namespace App\Modules\GestionRRHH\Services\Novedades;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class ActorNovedadService
{
    public function buscarUsuarioActivoPorDocumento(string $documento): ?User
    {
        $documento = trim($documento);
        if ($documento === '') {
            return null;
        }

        return User::query()
            ->where('UsuarioEstado', 'ACTIVO')
            ->whereHas('persona', function ($query) use ($documento) {
                $query->where('PerNumDoc', $documento)
                    ->where('PerEstado', 'ACTIVO');
            })
            ->first();
    }

    public function obtenerCorreoUsuarioPorDocumento(string $documento): string
    {
        $usuario = $this->buscarUsuarioActivoPorDocumento($documento);
        if ($usuario) {
            $correoUsuario = trim((string) ($usuario->persona->datos->PerEmail ?? ''));
            if ($correoUsuario !== '') {
                return $correoUsuario;
            }
        }

        $documento = trim($documento);
        if ($documento === '') {
            return '';
        }

        $persona = DB::connection('oracle')
            ->table('per_personas')
            ->where('identificacion', $documento)
            ->where('estado', 'ACTIVO')
            ->where('estborrado', 0)
            ->select('dirweb')
            ->first();

        return trim((string) ($persona->dirweb ?? ''));
    }
}
