<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Modulo extends Model
{
    protected $table = "modulos";
    protected $primaryKey = "IdModulo";
    protected $fillable = [
        'ModNom', 'ModDesc', 'ModEstado', 
        'Mod_Padre_Id', 'ModRuta', 'ModPermiso',
        'ModIcono', 'ModFechReg', 'ModHorReg',
    ];

    public $timestamps = false;

    public function submodulos(): HasMany{
        return $this->hasMany(Modulo::class, 'Mod_Padre_Id');
    }

    public function padre(): belongsTo{
        return $this->belongsTo(Modulo::class, 'Mod_Padre_Id');
    }
}
