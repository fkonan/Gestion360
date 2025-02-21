<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Municipio extends Model
{
    use HasFactory;

    protected $table = "_municipios";
    protected $primaryKey = "IdMunicipio";
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        "IdMunicipio","MunNon","MunNomMin","IdDepartamento"
    ];

    public function departamento(): BelongsTo{
        return $this->belongsTo(Departamento::class,'IdDepartamento','IdDepartamento');
    }

    public function personasNac(): HasMany{
        return $this->hasMany(Persona::class,'PerLugNac','IdMunicipio');
    }

    public function personasExp(): HasMany{
        return $this->hasMany(Persona::class,'PerLugarExp','IdMunicipio');
    }
}
