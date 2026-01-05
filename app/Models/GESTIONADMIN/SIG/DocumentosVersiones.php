<?php

namespace App\Models\GESTIONADMIN\SIG;

use Illuminate\Database\Eloquent\Model;

class DocumentosVersiones extends Model
{
  protected $connection = 'mysql-gestion-admin';
  protected $table = "sig_documento_versiones";
  protected $primaryKey = "id";

  public $timestamps = false;

  public function documento()
  {
    return $this->belongsTo(Documentos::class, 'documento_id');
  }
}
