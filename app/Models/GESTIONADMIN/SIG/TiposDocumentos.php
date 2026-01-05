<?php

namespace App\Models\GESTIONADMIN\SIG;

use Illuminate\Database\Eloquent\Model;

class TiposDocumentos extends Model
{
  protected $connection = 'mysql-gestion-admin';
  protected $table = "sig_tipos_documento";
  protected $primaryKey = "id";

  public $timestamps = false;

  public function documentos()
  {
    return $this->hasMany(Documentos::class, 'id_tipo_doc');
  }
}
