<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class EmpleadoService
{
    //empleado activo en LOGTRANS
    public static function esEmpleadoActivo($identificacion): bool
    {
        return DB::connection('oracle')
            ->table('per_empresapersonas as ep')
            ->join('per_contrato_persona as cp', 'ep.pe_id_pe', '=', 'cp.pe_id_pe')
            ->where('ep.activo', 1)
            ->where('ep.estborrado', 0)
            ->where('ep.tp_id', 1)
            ->where('cp.identificacion', $identificacion)
            ->exists();
    }

    //Obtener documentos válidos desde Oracle (solo empleados activos) y se cachean por 5 minutos
    public static  function documentosEmpleadosValidos(): array{
        return Cache::remember('empleados_oracle', 300, function () {
            return DB::connection('oracle')
                ->table('per_empresapersonas as ep')
                ->join('per_contrato_persona as cp', 'ep.pe_id_pe', '=', 'cp.pe_id_pe')
                ->where('ep.activo', 1)
                ->where('ep.estborrado', 0)
                ->where('ep.tp_id', 1)
                ->pluck('cp.identificacion')
                ->toArray();
        });
    }

    //lista de centros de costo para un grupo de identificaciones
    public static function obtenerCentrosCostoMasivos(array $identificaciones): array{
        return DB::connection('oracle')
            ->table('per_contrato_persona as cp')
            ->join('per_empresapersonas as ep', 'cp.pe_id_pe', '=', 'ep.pe_id_pe')
            ->join('per_cargoccostos as cc', 'ep.cc_id', '=', 'cc.id')
            ->join('per_centrocostos as ct', 'cc.ct_codigo', '=', 'ct.codigo')
            ->whereIn('cp.identificacion', $identificaciones)
            ->where('ep.activo', 1)
            ->where('ep.estborrado', 0)
            ->where('cc.activo', 1)
            ->where('cc.estborrado', 0)
            ->where('ct.estado', 1)
            ->where('ct.estborrado', 0)
            ->pluck('ct.descripcion', 'cp.identificacion') 
            ->toArray();
    }
}
