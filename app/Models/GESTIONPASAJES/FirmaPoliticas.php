<?php

namespace App\Models\GESTIONPASAJES;

use Illuminate\Database\Eloquent\Model;

class FirmaPoliticas extends Model
{
  protected $connection = "mysql-gestion-pasajes";
  protected $table = "_FirConductores";
  protected $primaryKey = "IdFirma";
  public $timestamps = false;
  protected $appends = ['nombre_politica'];

  public function politica()
  {
    return $this->belongsTo(ConfigPoliticas::class, 'PoliticaId', 'id');
  }

  public function getNombrePoliticaAttribute()
  {
    return $this->politica ? $this->politica->politica : null;
  }
}
