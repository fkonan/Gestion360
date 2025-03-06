<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasFactory;

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
}
