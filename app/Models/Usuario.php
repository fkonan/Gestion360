<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Usuario extends Model
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
}
