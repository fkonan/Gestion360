<?php

namespace App\Models\GESTIONADMIN;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Modulo extends Model
{
     /**
     * @property string $ModEstado
     */

    protected $connection = 'mysql-gestion-admin';
    protected $table = "_modulos";
    protected $primaryKey = "IdModulo";
    protected $fillable = [
        'ModNom', 'ModDesc', 'ModuloEstado', 'ModRuta', 
        'ModPermiso','ModIcono', 'ModFechReg', 'ModHorReg',
    ];

    public $timestamps = false;

    public function submodulos(): HasMany{
        return $this->hasMany(SubModulo::class, 'ModuloId','IdModulo');
    }

    public function permisos(): HasMany{
        return $this->hasMany(Permisos::class, 'ModuloId', 'IdModulo');
    }

    public function getNombreFormateadoAttribute() {
        $nombreFormateado = mb_convert_case(mb_strtolower($this->ModNom, 'UTF-8'), MB_CASE_TITLE, "UTF-8");
        $nombreFormateado = str_replace('Rr-Hh', 'RR-HH', $nombreFormateado);
        return $nombreFormateado;
    }

    // ModuloEstado => ModEstado
    public function getModEstadoAttribute()
    {
        return $this->attributes['ModuloEstado'];
    }

    public function setModEstadoAttribute($value)
    {
        $this->attributes['ModuloEstado'] = $value;
    }

    // ModDesc => ModDes
    public function getModDescAttribute()
    {
        return $this->attributes['ModDes'];
    }

    public function setModDescAttribute($value)
    {
        $this->attributes['ModDes'] = $value;
    }


    // ModFechReg => ModFecReg
    public function getModFechRegAttribute()
    {
        return $this->attributes['ModFecReg'];
    }

    public function setModFechRegAttribute($value)
    {
        $this->attributes['ModFecReg'] = $value;
    }

    // ModHorReg => ModHoReg
    public function getModHorRegAttribute()
    {
        return $this->attributes['ModHoReg'];
    }

    public function setModHorRegAttribute($value)
    {
        $this->attributes['ModHoReg'] = $value;
    }
}
