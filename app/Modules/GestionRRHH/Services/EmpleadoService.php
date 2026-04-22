<?php

namespace App\Modules\GestionRRHH\Services;

use App\Modules\GestionRRHH\Models\PerPersonas;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class EmpleadoService
{
  // empleado activo en LOGTRANS
  public static function esEmpleadoActivo($identificacion, $retornarPersona = false): bool|object|null
  {
    $query = DB::connection('oracle')
      ->table('per_empresapersonas as ep')
      ->join('per_personas as p', 'ep.pe_id_pe', '=', 'p.id')
      ->leftJoin('gen_municipios as m', 'm.id', '=', 'p.MU_NACIMIENTO')
      ->where('ep.activo', 1)
      ->where('ep.estborrado', 0)
      ->whereIn('ep.tp_id', [1, 6, 12])
      ->where('p.identificacion', $identificacion);

    if ($retornarPersona) {
      return $query->first() ?? null; // Devuelve el primer registro encontrado
    }

    return $query->exists(); // Solo valida existencia
  }

  // ID per_persona LOGTRANS
  public static function idPersonaLogtrans($identificacion)
  {
    return PerPersonas::where('identificacion', $identificacion)->value('id');
  }

  // Obtener documentos válidos desde Oracle (solo empleados activos) y se cachean por 5 minutos
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

  // lista de centros de costo para un grupo de identificaciones
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

  public static function codigoCentroCosto($identificacion)
  {
    return DB::connection('oracle')
      ->table('per_personas as p')
      ->join('per_empresapersonas as ep', 'p.id', '=', 'ep.pe_id_pe')
      ->join('per_cargoccostos as cc', 'ep.cc_id', '=', 'cc.id')
      ->join('per_centrocostos as ct', 'cc.ct_codigo', '=', 'ct.codigo')
      ->where('p.identificacion', $identificacion)
      ->where('ep.activo', 1)
      ->where('ep.estborrado', 0)
      ->where('cc.activo', 1)
      ->where('cc.estborrado', 0)
      ->where('ct.estado', 1)
      ->where('ct.estborrado', 0)
      ->value('ct.codigo');
  }

  public static function obtenerAgencia($identificacion)
  {
    return DB::connection('oracle')
      ->table('per_personas as p')
      ->join('per_empresapersonas as ep', 'p.id', '=', 'ep.pe_id_pe')
      ->join('per_cargoccostos as cc', 'ep.cc_id', '=', 'cc.id')
      ->join('per_centrocostos as ct', 'cc.ct_codigo', '=', 'ct.codigo')
      ->where('p.identificacion', $identificacion)
      ->where('ep.activo', 1)
      ->where('ep.estborrado', 0)
      ->where('cc.activo', 1)
      ->where('cc.estborrado', 0)
      ->where('ct.estado', 1)
      ->where('ct.estborrado', 0)
      ->value('ct.pe_id');
  }

  // Verifica si un empleado tiene un rol específico
  public static function rolesLogtrans($identificacion): array
  {
    return DB::connection('oracle')
      ->table('per_personas as p')
      ->join('per_empresapersonas as ep', 'ep.pe_id_pe', '=', 'p.id')
      ->join('arq_personaroles as pr', 'pr.ep_id', '=', 'ep.id')
      ->join('arq_roles as r', 'r.id', '=', 'pr.ro_id')
      ->where('p.identificacion', $identificacion)
      ->where('r.estborrado', 0)
      ->where('p.estado', 'ACTIVO')
      ->where('p.estborrado', 0)
      ->where('ep.activo', 1)
      ->where('ep.estborrado', 0)
      ->where('pr.activo', 1)
      ->where('pr.estborrado', 0)
      ->orderBy('r.descripcion')
      ->pluck('pr.ro_id')
      ->map(fn($rol) => trim((string) $rol))
      ->filter()
      ->unique()
      ->values()
      ->all();
  }

  // Asignar roles en autogestion según el rol de Logtrans
  public static function asignarRolesLogtrans($identificacion, $user)
  {
    $esJefeLogtrans = self::esJefeLogtrans($identificacion);

    // Siempre quitar los roles existentes de Logtrans
    $rolesConLogtrans = $user->roles()
      ->whereNotNull('idLogtrans')
      ->pluck('id')
      ->toArray();

    if (! empty($rolesConLogtrans)) {
      $user->roles()->detach($rolesConLogtrans);
    }

    $rolesLogtrans = EmpleadoService::rolesLogtrans($identificacion);
    if ($esJefeLogtrans) {
      $rolesLogtrans[] = '10';
      $rolesLogtrans = collect($rolesLogtrans)
        ->map(fn($rol) => trim((string) $rol))
        ->filter()
        ->unique()
        ->values()
        ->all();
    }

    // Solo asignar nuevos roles si rolesLogtrans no está vacío
    if (! empty($rolesLogtrans)) {
      $rolesParaAsignar = Role::query()
        ->whereNotNull('idLogtrans')
        ->get()
        ->filter(function ($role) use ($rolesLogtrans) {
          $idsLogtransRol = self::normalizarIdsLogtrans($role->idLogtrans);

          return ! empty(array_intersect($rolesLogtrans, $idsLogtransRol));
        });

      if ($rolesParaAsignar->isNotEmpty()) {
        $user->assignRole($rolesParaAsignar->values());
      }
    }

    // El rol local JEFE (id 10) no depende de idLogtrans, se sincroniza aparte.
    self::sincronizarRolJefeAutogestion($user, $esJefeLogtrans);
  }

  private static function normalizarIdsLogtrans(?string $idsLogtrans): array
  {
    if (blank($idsLogtrans)) {
      return [];
    }

    return collect(explode(',', $idsLogtrans))
      ->map(fn($id) => trim($id))
      ->filter()
      ->unique()
      ->values()
      ->all();
  }

  private static function esJefeLogtrans(?string $identificacion): bool
  {
    $identificacion = trim((string) $identificacion);
    if ($identificacion === '') {
      return false;
    }

    return DB::connection('oracle')
      ->table('per_personas as p')
      ->join('per_empresapersonas as ep', 'ep.pe_id_pe', '=', 'p.id')
      ->join('per_cargoccostos as cc', 'cc.id', '=', 'ep.cc_id')
      ->join('per_centrocostos as ct', 'ct.codigo', '=', 'cc.ct_codigo')
      ->where('p.identificacion', $identificacion)
      ->where('p.estado', 'ACTIVO')
      ->where('p.estborrado', 0)
      ->whereIn('ep.tp_id', [1, 11])
      ->where('ep.activo', 1)
      ->where('ep.estborrado', 0)
      ->whereNull('ep.fecfin')
      ->where('cc.activo', 1)
      ->where('cc.estborrado', 0)
      ->where('ct.estado', 1)
      ->where('ct.estborrado', 0)
      ->whereExists(function ($query) {
        $query->select(DB::raw(1))
          ->from('per_cargoccostos as cce')
          ->join('per_empresapersonas as epe', 'epe.cc_id', '=', 'cce.id')
          ->join('per_centrocostos as cte', 'cte.codigo', '=', 'cce.ct_codigo')
          ->whereColumn('cce.id_cargosup', 'cc.ca_codigo')
          ->whereColumn('cce.ct_codigo', 'cc.ct_codigo')
          ->where('cce.activo', 1)
          ->where('cce.estborrado', 0)
          ->whereIn('epe.tp_id', [1, 11])
          ->where('epe.activo', 1)
          ->where('epe.estborrado', 0)
          ->whereNull('epe.fecfin')
          ->where('cte.estado', 1)
          ->where('cte.estborrado', 0);
      })
      ->exists();
  }

  private static function sincronizarRolJefeAutogestion($user, bool $esJefeLogtrans): void
  {
    $rolJefe = Role::query()
      ->where('id', 10)
      ->first();

    if (! $rolJefe) {
      return;
    }

    $tieneRolJefe = $user->roles()
      ->where('roles.id', $rolJefe->id)
      ->exists();

    if ($esJefeLogtrans && ! $tieneRolJefe) {
      $user->assignRole($rolJefe);
      return;
    }

    if (! $esJefeLogtrans && $tieneRolJefe) {
      $user->removeRole($rolJefe);
    }
  }
}
