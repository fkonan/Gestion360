<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class EmpleadoService
{
    public static function esEmpleadoActivo($identificacion): bool
    {
        return DB::connection('oracle')
            ->table('PER_CONTRATO_PERSONA')
            ->where('identificacion', $identificacion)
            ->where('estado', 1)
            ->where('estborrado', 0)
            ->exists();
    }
}
