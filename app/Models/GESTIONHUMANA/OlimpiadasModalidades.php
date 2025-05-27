<?php

namespace App\Models\GESTIONHUMANA;

use Illuminate\Database\Eloquent\Model;

class OlimpiadasModalidades extends Model
{
    protected $connection = 'mysql-gestion-humana';
    protected $table = "oli_modalidades";
    protected $primaryKey = "IdModalidad";

    public $timestamps = false;

    public function deportes(){
        return $this->hasMany(OlimpiadasDepMod::class, 'ModalidadId', 'IdModalidad');
    }
}
