<?php

namespace App\Models;

use App\Models\GESTIONADMIN\Persona;
use App\Models\GESTIONADMIN\Sesion;
use App\Models\GESTIONHUMANA\Enfermedades;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory;
    use HasRoles;

    protected $guard_name = 'web';
    public const SUPER_ADMIN_ROLE = 'SUPER-ADMIN';

    protected $connection = 'mysql-gestion-admin';
    protected $table = "_usuarios";
    protected $primaryKey = "IdUsuario";
    public $timestamps = false;

    protected $fillable = [
        'IdUsuario','idPersona','Password',
        'UsuFecReg','UsuHorReg','UsuReg',
        'UsuarioEstado','Verificado',
    ];
  
    public function persona(): BelongsTo{
        return $this->belongsTo(Persona::class,'idPersona','IdPersona')
                    ->where('PerEstado', 'ACTIVO');
    }

     public function enfermedadesRegistradas(): HasMany{
        return $this->hasMany(Enfermedades::class,'IdUserReg','IdUsuario');
    }

    public function sesion(): HasMany{
        return $this->HasMany(Sesion::class,"IdUser","IdUsuario");
    }

    public function ultimaSesion(): HasMany{
        return $this->HasMany(Sesion::class,"IdUser","IdUsuario")->orderByDesc('IdSesion');
    }

    public function getAuthPassword(){
        return $this->Password;
    }

    //determina si tiene permisos un usuario y permite al super admin ignorar los permisos
    public function can($ability, $arguments = []){
        if (empty($ability)) {
            return false;
        }

        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->checkPermissionTo($ability); 
    }


    public function canAny($abilities, $arguments = []){
        if ($this->isSuperAdmin()) {
            return true;
        }
        return $this->hasAnyPermission($abilities);
    }

    private function isSuperAdmin(){
        return $this->hasRole(self::SUPER_ADMIN_ROLE);
    }

    public function getRolAttribute(){
        $roles = $this->getRoleNames();
        return $roles->isNotEmpty() ? $roles->first() : 'SIN ROL';
    }

    public function obtenerDescripcionCentroCosto(){
        $key = 'centrocosto_' . $this->persona->PerNumDoc;

        //se cachea el centro de costo del usuario ya que este no cambia seguido
        return Cache::remember($key, now()->addHours(6), function () {
            return DB::connection('oracle')
                ->table('per_contrato_persona as cp')
                ->join('per_empresapersonas as ep', 'cp.pe_id_pe', '=', 'ep.pe_id_pe')
                ->join('per_cargoccostos as cc', 'ep.cc_id', '=', 'cc.id')
                ->join('per_centrocostos as ct', 'cc.ct_codigo', '=', 'ct.codigo')
                ->where('cp.identificacion', $this->persona->PerNumDoc)
                ->where('ep.activo', 1)
                ->where('ep.estborrado', 0)
                ->where('cc.activo', 1)
                ->where('cc.estborrado', 0)
                ->where('ct.estado', 1)
                ->where('ct.estborrado', 0)
                ->pluck('ct.descripcion')
                ->first();
        });
    }

}
