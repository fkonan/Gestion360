<?php

namespace App\Models\GESTIONADMIN;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersonaDatos extends Model
{
  use HasFactory;

  protected $connection = 'mysql-gestion-admin';
  protected $table = "_personas_datos";
  protected $primaryKey = "IdPersonaDatos";
  public $timestamps = false;

  protected $fillable = [
    "PerTelefono",
    "PerEmail",
    "PerDir",
    "PerBar",
    "PerMunRes",
    "PerFecReg",
    "PerHorReg",
    "PerFecUltAct",
    "PerAutTra",
    "PerComDat",
    "PerConPol",
    "PerAutNot"
  ];

  public function persona()
  {
    return $this->belongsTo(Persona::class, "IdPersona", "IdPersona");
  }
}
