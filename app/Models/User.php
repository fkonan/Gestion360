<?php

namespace App\Models;

use App\Models\GESTIONADMIN\Persona;
use App\Models\GESTIONADMIN\Sesion;
use App\Models\GESTIONHUMANA\Enfermedades;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        return $this->belongsTo(Persona::class,'idPersona','IdPersona');
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

    public function can($ability, $arguments = []){
        if ($this->isSuperAdmin()) {
            return true;
        }
        return $this->hasPermissionTo($ability);
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

    public function enfermedadesRegistradas(): HasMany{
        return $this->hasMany(Enfermedades::class,'IdUserReg','IdUsuario');
    }

    public function getRolAttribute(){
        $roles = $this->getRoleNames();
        return $roles->isNotEmpty() ? $roles->first() : 'SIN ROL';
    }

}
