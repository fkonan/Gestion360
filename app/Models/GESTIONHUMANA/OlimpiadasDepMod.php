<?php

namespace App\Models\GESTIONHUMANA;

use Illuminate\Database\Eloquent\Model;

class OlimpiadasDepMod extends Model
{
    protected $connection = 'mysql-gestion-humana';
    protected $table = "oli_deportemodalidad";
    protected $primaryKey = "IdDepMod";

    public $timestamps = false;

    public function deporte(){
        return $this->belongsTo(OlimpiadasDeportes::class, 'DeporteId', 'idDeporte');
    }

    public function modalidad(){
        return $this->belongsTo(OlimpiadasModalidades::class, 'ModalidadId', 'IdModalidad');
    }
}
