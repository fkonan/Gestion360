<?php

namespace App\Models;

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
    if ($this->hasRole('Super Admin')) {
        return true;
    }
    return $this->hasPermissionTo($ability);
    }

    public function canAny($abilities, $arguments = []){
        if ($this->hasRole('Super Admin')) {
            return true;
        }

        foreach ($abilities as $ability) {
            if ($this->hasAnyPermission($ability)) {
                return true;
            }
        }
        return false;
    }

}
