<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Persona extends Model
{
    use HasFactory;

    protected $table ="_personas";
    protected $primaryKey ="IdPersona";
    public $timestamps = false;

    protected $fillable =[
        "PerTipoDoc" , "PerNumDoc", "PerApellidos", "PerNombres", "PerGenero", 
        "PerFecNac", "PerLugNac", "PerFecExp", "PerLugExp", "PerGruRh", 
        "PerFechReg", "PerHorReg", "PerEstado"
    ];

    public function municipioNac(): BelongsTo{
        return $this->belongsTo(Municipio::class,"PerLugNac","IdMunicipio");
    }

    public function minicipioExp(): BelongsTo{
        return $this->belongsTo(Municipio::class,"PerLugExp","IdMunicipio");
    }

    public function tipoDocumento(): BelongsTo{
        return $this->belongsTo(TipoDocumento::class,"PerTipoDoc","id");
    }
}
