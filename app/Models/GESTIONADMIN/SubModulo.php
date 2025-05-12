<?php

namespace App\Models\GESTIONADMIN;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubModulo extends Model
{
    /**
     * @property string $ModNom
     * @property string $ModDesc
     * @property string $ModEstado
     * @property string $ModRuta
     * @property string $ModPermiso
     * @property string $ModIcono
     * @property string $ModFechReg
     * @property string $ModHorReg
     */

    protected $connection = 'mysql-gestion-admin';
    protected $table = "_submodulos";
    protected $primaryKey = "IdSubModulo";
    protected $fillable = [
        'SubModNom', 'SubModDes', 'SubModuloEstado', 'SubModRuta', 
        'SubModPermiso','SubModIcono', 'SubModFecReg', 'SubModHoReg',
    ];

    public $timestamps = false;

    public function padre(): belongsTo{
        return $this->belongsTo(Modulo::class, 'ModuloId','IdModulo');
    }

    public function permisos(): HasMany{
        return $this->hasMany(Permisos::class, 'ModuloId', 'IdModulo');
    }

    public function getNombreFormateadoAttribute() {
        $nombreFormateado = mb_convert_case(mb_strtolower($this->SubModNom, 'UTF-8'), MB_CASE_TITLE, "UTF-8");
        $nombreFormateado = str_replace('Rr-Hh', 'RR-HH', $nombreFormateado);
        return $nombreFormateado;
    }


    // Accessors y Mutators para SubModNom => ModNom
    public function getModNomAttribute()
    {
        return $this->attributes['SubModNom'];
    }

    public function setModNomAttribute($value)
    {
        $this->attributes['SubModNom'] = $value;
    }

    // IdSubModulo => IdModulo
    public function getIdModuloAttribute()
    {
        return $this->attributes['IdSubModulo'];
    }

    public function setIdModuloAttribute($value)
    {
        $this->attributes['IdSubModulo'] = $value;
    }


    // SubModDesc => ModDesc
    public function getModDescAttribute()
    {
        return $this->attributes['SubModDes'];
    }

    public function setModDescAttribute($value)
    {
        $this->attributes['SubModDes'] = $value;
    }

    // SubModEstado => ModEstado
    public function getModEstadoAttribute()
    {
        return $this->attributes['SubModuloEstado'];
    }

    public function setModEstadoAttribute($value)
    {
        $this->attributes['SubModuloEstado'] = $value;
    }

    // SubModRuta => ModRuta
    public function getModRutaAttribute()
    {
        return $this->attributes['SubModRuta'];
    }

    public function setModRutaAttribute($value)
    {
        $this->attributes['SubModRuta'] = $value;
    }

    // SubModPermiso => ModPermiso
    public function getModPermisoAttribute()
    {
        return $this->attributes['SubModPermiso'];
    }

    public function setModPermisoAttribute($value)
    {
        $this->attributes['SubModPermiso'] = $value;
    }

    // SubModIcono => ModIcono
    public function getModIconoAttribute()
    {
        return $this->attributes['SubModIcono'];
    }

    public function setModIconoAttribute($value)
    {
        $this->attributes['SubModIcono'] = $value;
    }

    // SubModFechReg => ModFechReg
    public function getModFechRegAttribute()
    {
        return $this->attributes['SubModFecReg'];
    }

    public function setModFechRegAttribute($value)
    {
        $this->attributes['SubModFecReg'] = $value;
    }

    // SubModHorReg => ModHorReg
    public function getModHorRegAttribute()
    {
        return $this->attributes['SubModHoReg'];
    }

    public function setModHorRegAttribute($value)
    {
        $this->attributes['SubModHoReg'] = $value;
    }

    protected $appends = [
        'ModNom', 'ModDesc', 'ModEstado', 'ModRuta',
        'ModPermiso', 'ModIcono', 'ModFechReg', 'ModHorReg',
    ];
    
    protected $hidden = [
        'SubModNom', 'SubModDesc', 'SubModEstado', 'SubModRuta',
        'SubModPermiso', 'SubModIcono', 'SubModFechReg', 'SubModHorReg',
    ];

}
