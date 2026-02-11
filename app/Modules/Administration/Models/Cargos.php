<?php

namespace App\Modules\Administration\Models;

use Illuminate\Database\Eloquent\Model;

class Cargos extends Model
{
    protected $connection = 'mysql-gestion-pasajes';

    protected $table = '_cargos';

    protected $primaryKey = 'id';

    public $timestamps = false;

    public function funciones()
    {
        return $this->belongsToMany(FuncionesPasajes::class, 'car_fun', 'idCar', 'idFun');
    }
}
