<?php

namespace App\Modules\GestionRRHH\Models;

use Illuminate\Database\Eloquent\Model;

class OlimpiadasDeportes extends Model
{
    protected $connection = 'mysql-gestion-humana';

    protected $table = 'oli_deporte';

    protected $primaryKey = 'idDeporte';

    public $timestamps = false;

    public function modalidades()
    {
        return $this->hasMany(OlimpiadasDepMod::class, 'DeporteId', 'idDeporte');
    }
}
