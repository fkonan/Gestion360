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

  public static function codigoCentroCosto($identificacion, ?int $sucursalId = null)
  {
    $baseQuery = DB::connection('oracle')
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
      ->where('ct.estborrado', 0);

    if ($sucursalId) {
      $centroCostoSucursal = (clone $baseQuery)
        ->where('ct.pe_id', $sucursalId)
        ->orderBy('ep.tp_id', 'asc')
        ->orderByDesc('ep.id')
        ->value('ct.codigo');

      if ($centroCostoSucursal) {
        return $centroCostoSucursal;
      }
    }

    return $baseQuery
      ->orderBy('ep.tp_id', 'asc')
      ->orderByDesc('ep.id')
      ->value('ct.codigo');
  }

  public static function codigoCentroCostoPorSucursal(int $sucursalId): ?string
  {
    return DB::connection('oracle')
      ->table('per_centrocostos as ct')
      ->where('ct.pe_id', $sucursalId)
      ->where('ct.estado', 1)
      ->where('ct.estborrado', 0)
      ->orderBy('ct.codigo')
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

  // valida si la persona tiene contrato activo como conductor (tp_id = 11)
  // por defecto valida sobre empleador 6761 (Copetran), pero permite otro empleador
  public static function esConductorActivo(
    string $identificacion,
    ?int $empleadorId = 6761,
    bool $retornarRegistro = false
  ): bool|object|null {
    $identificacion = trim($identificacion);
    if ($identificacion === '') {
      return $retornarRegistro ? null : false;
    }

    $query = DB::connection('oracle')
      ->table('per_personas as p')
      ->join('per_empresapersonas as ep', 'ep.pe_id_pe', '=', 'p.id')
      ->where('p.identificacion', $identificacion)
      ->where('p.estado', 'ACTIVO')
      ->where('p.estborrado', 0)
      ->where('ep.tp_id', 11)
      ->where('ep.activo', 1)
      ->where('ep.estborrado', 0)
      ->whereNull('ep.fecfin');

    if ($empleadorId !== null) {
      $query->where('ep.pe_id_emp', $empleadorId);
    }

    if ($retornarRegistro) {
      return $query->first([
        'p.id as pe_id',
        'p.identificacion',
        'ep.id as ep_id',
        'ep.pe_id_emp',
        'ep.cc_id',
      ]) ?? null;
    }

    return $query->exists();
  }

  // valida si la persona tiene relacion activa como asociado (tp_id = 13)
  public static function esAsociadoActivo(string $identificacion, bool $retornarRegistro = false): bool|object|null
  {
    $identificacion = trim($identificacion);
    if ($identificacion === '') {
      return $retornarRegistro ? null : false;
    }

    $query = DB::connection('oracle')
      ->table('per_personas as pp')
      ->join('per_empresapersonas as pe', 'pe.pe_id_pe', '=', 'pp.id')
      ->where('pp.identificacion', $identificacion)
      ->where('pp.estado', 'ACTIVO')
      ->where('pp.estborrado', 0)
      ->where('pe.tp_id', 13)
      ->where('pe.activo', 1)
      ->where('pe.estborrado', 0)
      ->whereNull('pe.fecfin');

    if ($retornarRegistro) {
      return $query->first([
        'pp.id as pe_id',
        'pp.identificacion',
        'pe.id as ep_id',
        'pe.pe_id_emp',
        'pe.cc_id',
      ]) ?? null;
    }

    return $query->exists();
  }

  // obtiene conductores activos relacionados a un asociado por pe_id_emp
  public static function obtenerConductoresActivosDeAsociado(string $identificacionAsociado): array
  {
    $identificacionAsociado = trim($identificacionAsociado);
    if ($identificacionAsociado === '') {
      return [];
    }

    $rows = DB::connection('oracle')
      ->table('per_personas as asoc')
      ->join('per_empresapersonas as pe', 'pe.pe_id_emp', '=', 'asoc.id')
      ->join('per_personas as cond', 'cond.id', '=', 'pe.pe_id_pe')
      ->leftJoin('per_cargoccostos as cc', 'cc.id', '=', 'pe.cc_id')
      ->leftJoin('per_centrocostos as ct', 'ct.codigo', '=', 'cc.ct_codigo')
      ->where('asoc.identificacion', $identificacionAsociado)
      ->where('asoc.estado', 'ACTIVO')
      ->where('asoc.estborrado', 0)
      ->where('pe.tp_id', 11)
      ->where('pe.activo', 1)
      ->where('pe.estborrado', 0)
      ->whereNull('pe.fecfin')
      ->where('cond.estado', 'ACTIVO')
      ->where('cond.estborrado', 0)
      ->orderBy('cond.papellido')
      ->orderBy('cond.sapellido')
      ->orderBy('cond.pnombre')
      ->get([
        'cond.identificacion as doc_empleado',
        DB::raw("TRIM(cond.pnombre || ' ' || NVL(cond.snombre, '')) || ' ' || TRIM(cond.papellido || ' ' || NVL(cond.sapellido, '')) as nombre_empleado"),
        DB::raw('NVL(ct.codigo, \'\') as codigo_centro_costo'),
        DB::raw('NVL(ct.descripcion, \'\') as centro_costo'),
      ]);

    return $rows->map(function ($row) {
      return [
        'doc_empleado' => trim((string) ($row->doc_empleado ?? '')),
        'nombre_empleado' => trim((string) ($row->nombre_empleado ?? '')),
        'codigo_centro_costo' => trim((string) ($row->codigo_centro_costo ?? '')),
        'centro_costo' => trim((string) ($row->centro_costo ?? '')),
      ];
    })
      ->filter(fn($item) => ($item['doc_empleado'] ?? '') !== '')
      ->unique('doc_empleado')
      ->values()
      ->all();
  }

  // obtiene codigo y descripcion del centro de costo activo del empleado
  public static function obtenerCentroCostoEmpleado(string $identificacion): ?array
  {
    $identificacion = trim($identificacion);
    if ($identificacion === '') {
      return null;
    }

    $row = DB::connection('oracle')
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
      ->orderBy('ep.tp_id', 'asc')
      ->orderByDesc('ep.id')
      ->first([
        'ct.codigo as codigo',
        'ct.descripcion as descripcion',
        'ct.pe_id as sucursal_id',
      ]);

    if (! $row) {
      return null;
    }

    return [
      'codigo' => trim((string) ($row->codigo ?? '')),
      'descripcion' => trim((string) ($row->descripcion ?? '')),
      'sucursal_id' => isset($row->sucursal_id) ? (int) $row->sucursal_id : null,
    ];
  }

  // resuelve el asociado de un conductor usando el empleador relacionado en per_empresapersonas
  // SQL base:
  // select asoc.identificacion, asoc.pnombre, asoc.snombre, asoc.papellido, asoc.sapellido, asoc.dirweb
  // from per_personas pp
  // join per_empresapersonas pe on pp.id = pe.pe_id_pe
  // join per_personas asoc on asoc.id = pe.pe_id_emp
  // where pe.activo = 1
  //   and pp.identificacion = :documento
  //   and pe.estborrado = 0
  //   and pe.pe_id_emp not in (6761)
  //   and pe.fecfin is null
  //   and pe.tp_id = 11
  public static function obtenerAsociadoDeConductor(string $identificacionConductor): ?array
  {
    $identificacionConductor = trim($identificacionConductor);
    if ($identificacionConductor === '') {
      return null;
    }

    $asociado = DB::connection('oracle')
      ->table('per_personas as pp')
      ->join('per_empresapersonas as pe', 'pp.id', '=', 'pe.pe_id_pe')
      ->join('per_personas as asoc', 'asoc.id', '=', 'pe.pe_id_emp')
      ->where('pe.activo', 1)
      ->where('pp.identificacion', $identificacionConductor)
      ->where('pe.estborrado', 0)
      ->whereNotIn('pe.pe_id_emp', [6761])
      ->whereNull('pe.fecfin')
      ->where('pe.tp_id', 11)
      ->orderByDesc('pe.id')
      ->first([
        'asoc.id',
        'asoc.identificacion',
        'asoc.pnombre',
        'asoc.snombre',
        'asoc.papellido',
        'asoc.sapellido',
        'asoc.dirweb',
        'pe.pe_id_emp',
      ]);

    if (! $asociado) {
      return null;
    }

    $nombre = trim(implode(' ', array_filter([
      trim((string) ($asociado->pnombre ?? '')),
      trim((string) ($asociado->snombre ?? '')),
      trim((string) ($asociado->papellido ?? '')),
      trim((string) ($asociado->sapellido ?? '')),
    ])));

    return [
      'id' => (int) ($asociado->id ?? 0),
      'identificacion' => trim((string) ($asociado->identificacion ?? '')),
      'nombre' => $nombre,
      'correo' => trim((string) ($asociado->dirweb ?? '')),
      'fuente' => 'pe_id_emp_relacion',
      'empleador_id' => isset($asociado->pe_id_emp) ? (int) $asociado->pe_id_emp : null,
    ];

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
