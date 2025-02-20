<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Persona extends Model
{
    use HasFactory;

    protected $table ="_personas";
    protected $primarykey ="IdPersona";

    protected $fillable =[
        "PerTipDoc" , "PerNumDoc", "PerApellidos", "PerNombres", "PerGenero", 
        "PerFecNac", "PerLugNac", "PerFecExp", "PerLugExp", "PerGruRh", "PerFechReg", "PerEstado"
    ];
}
