<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Services\EmpleadoService;

class LoginValidatorService
{
    public function validar(User $user, string $identificacion): ?array
    {
        if (!EmpleadoService::esEmpleadoActivo($identificacion)) {
            return ['message' => 'Solo los empleados activos pueden iniciar sesión.', 'type' => 'danger'];
        }

        if ($user->persona->PerEstado === 'INACTIVO') {
            return ['message' => 'Persona inactiva, contacte con un administrador', 'type' => 'warning'];
        }

        return match($user->UsuarioEstado) {
            'INACTIVO' => ['message' => 'Usuario inactivo, contacte con un administrador', 'type' => 'warning'],
            'SUSPENDIDO' => ['message' => 'Usuario suspendido, contacte con un administrador', 'type' => 'warning'],
            default => null
        };
    }
}
