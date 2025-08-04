<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Role;

class EmpleadoService
{
   //empleado activo en LOGTRANS
   public static function esEmpleadoActivo($identificacion,$retornarPersona=false): bool|object
   {
      $query = DB::connection('oracle')
         ->table('per_empresapersonas as ep')
         ->join('per_personas as p', 'ep.pe_id_pe', '=', 'p.id')
         ->where('ep.activo', 1)
         ->where('ep.estborrado', 0)
         ->where('ep.tp_id', 1)
         ->where('p.identificacion', $identificacion);

      if ($retornarPersona) {
         return $query->first(); // Devuelve el primer registro encontrado
      }

      return $query->exists(); // Solo valida existencia
   }

   //Obtener documentos válidos desde Oracle (solo empleados activos) y se cachean por 5 minutos
   public static function documentosEmpleadosValidos(): array
   {
      return Cache::remember('empleados_oracle', 300, function () {
         return DB::connection('oracle')
            ->table('per_empresapersonas as ep')
            ->join('per_personas as p', 'ep.pe_id_pe', '=', 'p.id')
            ->where('ep.activo', 1)
            ->where('ep.estborrado', 0)
            ->where('ep.tp_id', 1)
            ->pluck('p.identificacion')
            ->toArray();
      });
   }

   //lista de centros de costo para un grupo de identificaciones
   public static function obtenerCentrosCostoMasivos(array $identificaciones): array
   {
      return DB::connection('oracle')
         ->table('per_personas as p')
         ->join('per_empresapersonas as ep', 'p.id', '=', 'ep.pe_id_pe')
         ->join('per_cargoccostos as cc', 'ep.cc_id', '=', 'cc.id')
         ->join('per_centrocostos as ct', 'cc.ct_codigo', '=', 'ct.codigo')
         ->whereIn('p.identificacion', $identificaciones)
         ->where('ep.activo', 1)
         ->where('ep.estborrado', 0)
         ->where('cc.activo', 1)
         ->where('cc.estborrado', 0)
         ->where('ct.estado', 1)
         ->where('ct.estborrado', 0)
         ->pluck('ct.descripcion', 'p.identificacion')
         ->toArray();
   }

   // Verifica si un empleado tiene un rol específico
   public static function rolesLogtrans($identificacion)
   {
      $rolesLogtrans = DB::connection('oracle')
         ->table('per_personas as p')
         ->join('per_empresapersonas as ep', 'ep.pe_id_pe', '=', 'p.id')
         ->join('arq_personaroles as pr', 'pr.ep_id', '=', 'ep.id')
         ->join('arq_roles as r', 'r.id', '=', 'pr.ro_id')
         ->where('p.identificacion', $identificacion)
         ->orderBy('r.descripcion')
         ->pluck('pr.ro_id');

      return $rolesLogtrans;
   }

   //Asignar roles en autogestion según el rol de Logtrans
   public static function asignarRolesLogtrans($identificacion, $user){
      $rolesLogtrans = EmpleadoService::rolesLogtrans($identificacion);

      //Roles que se asignan en autogestion según los roles de Logtrans
      $rolesParaAsignar = Role::whereIn('idLogtrans', $rolesLogtrans)->get();

      if ($rolesParaAsignar->isNotEmpty()) {
         $user->assignRole($rolesParaAsignar); 
      }
   }
}
