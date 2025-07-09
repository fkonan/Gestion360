<?php

namespace App\Services\Auth;

use App\Models\GESTIONADMIN\Sesion;

class RegistroSesionService
{
    public static function registrar(string $tipo, int $idUsuario): void
    {
        $now = now();

        Sesion::create([
            'IdUser' => $idUsuario,
            'SesionFechReg' => $now,
            'SesionHorReg' => $now,
            'SesionTipo' => strtoupper($tipo),
        ]);
    }
}