<?php

namespace App\Models;

use App\Models\GESTIONADMIN\Persona;
use App\Models\GESTIONADMIN\Sesion;
use App\Modules\GestionRRHH\Models\Enfermedades;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Traits\HasRoles;

/**
 * @method bool can(string|array $ability, array $arguments = [])
 * @method bool canAny(array $abilities, array $arguments = [])
 */
class User extends Authenticatable
{
    use HasFactory;
    use HasRoles;
    use Notifiable;

    protected $guard_name = 'web';

    public const SUPER_ADMIN_ROLE = 'SUPER-ADMIN';

    protected $connection = 'mysql-gestion-admin';

    protected $table = '_usuarios';

    protected $primaryKey = 'IdUsuario';

    public $timestamps = false;

    protected $with = ['persona'];

    protected $fillable = [
        'IdUsuario',
        'idPersona',
        'Password',
        'UsuFecReg',
        'UsuHorReg',
        'UsuReg',
        'UsuarioEstado',
        'Verificado',
    ];

    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'idPersona', 'IdPersona')
            ->where('PerEstado', 'ACTIVO');
    }

    public function enfermedadesRegistradas(): HasMany
    {
        return $this->hasMany(Enfermedades::class, 'IdUserReg', 'IdUsuario');
    }

    public function sesion(): HasMany
    {
        return $this->HasMany(Sesion::class, 'IdUser', 'IdUsuario');
    }

    public function ultimaSesion(): HasMany
    {
        return $this->HasMany(Sesion::class, 'IdUser', 'IdUsuario')->orderByDesc('IdSesion');
    }

    public function getAuthPassword()
    {
        return $this->Password;
    }

    // determina si tiene permisos un usuario y permite al super admin ignorar los permisos
    public function can($ability, $arguments = [])
    {
        if (empty($ability)) {
            return false;
        }

        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->checkPermissionTo($ability);
    }

    public function canAny($abilities, $arguments = [])
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->hasAnyPermission($abilities);
    }

    private function isSuperAdmin()
    {
        return $this->hasRole(self::SUPER_ADMIN_ROLE);
    }

    public function getRolAttribute()
    {
        $roles = $this->getRoleNames();

        return $roles->isNotEmpty() ? $roles->toArray() : ['SIN ROL'];
    }

    public function obtenerDescripcionCentroCosto()
    {
        $identificacion = trim((string) ($this->persona->PerNumDoc ?? ''));
        if ($identificacion === '') {
            return null;
        }

        $sucursalCajaActiva = $this->obtenerSucursalCajaActivaPorDocumento($identificacion);
        $key = 'centrocosto_'.$identificacion.'_sucursal_'.($sucursalCajaActiva ?? 'na');

        // se cachea el centro de costo del usuario ya que este no cambia seguido
        return Cache::remember($key, now()->addHours(6), function () use ($identificacion, $sucursalCajaActiva) {
            $baseQuery = $this->baseCentroCostoQuery($identificacion);

            if ($sucursalCajaActiva) {
                $descripcionSucursal = (clone $baseQuery)
                    ->where('ct.pe_id', $sucursalCajaActiva)
                    ->orderBy('ep.tp_id', 'asc')
                    ->orderByDesc('ep.id')
                    ->value('ct.descripcion');

                if ($descripcionSucursal) {
                    return $descripcionSucursal;
                }
            }

            return $baseQuery
                ->orderBy('ep.tp_id', 'asc')
                ->orderByDesc('ep.id')
                ->value('ct.descripcion');
        });
    }

    public function obtenerCodigoCentroCosto()
    {
        $identificacion = trim((string) ($this->persona->PerNumDoc ?? ''));
        if ($identificacion === '') {
            return null;
        }

        $sucursalCajaActiva = $this->obtenerSucursalCajaActivaPorDocumento($identificacion);
        $key = 'centrocosto_codigo_'.$identificacion.'_sucursal_'.($sucursalCajaActiva ?? 'na');

        return Cache::remember($key, now()->addHours(6), function () use ($identificacion, $sucursalCajaActiva) {
            $baseQuery = $this->baseCentroCostoQuery($identificacion);

            if ($sucursalCajaActiva) {
                $codigoSucursal = (clone $baseQuery)
                    ->where('ct.pe_id', $sucursalCajaActiva)
                    ->orderBy('ep.tp_id', 'asc')
                    ->orderByDesc('ep.id')
                    ->value('ct.codigo');

                if ($codigoSucursal) {
                    return $codigoSucursal;
                }
            }

            return $baseQuery
                ->orderBy('ep.tp_id', 'asc')
                ->orderByDesc('ep.id')
                ->value('ct.codigo');
        });
    }

    public static function obtenerCentrosCostosActivos()
    {
        return Cache::remember('centros_costos_activos', now()->addHours(6), function () {
            return DB::connection('oracle')
                ->table('per_contrato_persona as cp')
                ->join('per_empresapersonas as ep', 'cp.pe_id_pe', '=', 'ep.pe_id_pe')
                ->join('per_cargoccostos as cc', 'ep.cc_id', '=', 'cc.id')
                ->join('per_centrocostos as ct', 'cc.ct_codigo', '=', 'ct.codigo')
                ->where('ep.activo', 1)
                ->where('ep.estborrado', 0)
                ->where('cc.activo', 1)
                ->where('cc.estborrado', 0)
                ->where('ct.estado', 1)
                ->where('ct.estborrado', 0)
                ->distinct()
                ->orderBy('ct.descripcion')
                ->get(['ct.descripcion', 'ct.codigo']);
        });
    }

    private function baseCentroCostoQuery(string $identificacion)
    {
        return DB::connection('oracle')
            ->table('per_contrato_persona as cp')
            ->join('per_empresapersonas as ep', 'cp.pe_id_pe', '=', 'ep.pe_id_pe')
            ->join('per_cargoccostos as cc', 'ep.cc_id', '=', 'cc.id')
            ->join('per_centrocostos as ct', 'cc.ct_codigo', '=', 'ct.codigo')
            ->where('cp.identificacion', $identificacion)
            ->where('ep.activo', 1)
            ->where('ep.estborrado', 0)
            ->where('cc.activo', 1)
            ->where('cc.estborrado', 0)
            ->where('ct.estado', 1)
            ->where('ct.estborrado', 0);
    }

    private function obtenerSucursalCajaActivaPorDocumento(string $identificacion): ?int
    {
        $cacheKey = 'caja_activa_sucursal_'.$identificacion;

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($identificacion) {
            $personaId = DB::connection('oracle')
                ->table('PER_PERSONAS')
                ->where('IDENTIFICACION', $identificacion)
                ->where('ESTADO', 'ACTIVO')
                ->where('ESTBORRADO', 0)
                ->value('ID');

            if (! $personaId) {
                return null;
            }

            $idsucursal = DB::connection('oracle')
                ->table('TES_CAJATURNOS as T')
                ->join('TES_CAJAS as CJ', 'T.CJ_ID', '=', 'CJ.ID')
                ->join('PER_PERSONAS as P', 'CJ.PE_ID_AG', '=', 'P.ID')
                ->where('T.PE_ID', $personaId)
                ->where('T.ESTADO', 'T')
                ->where('T.ESTBORRADO', 0)
                ->orderByDesc('T.ID')
                ->value('P.ID');

            return $idsucursal ? (int) $idsucursal : null;
        });
    }

    // Verificar contraseña con SHA-1
    public function validateCredentials($password)
    {
        $sha1Driver = Hash::driver('sha1');

        return $sha1Driver->check($password, $this->clave);
    }
}
