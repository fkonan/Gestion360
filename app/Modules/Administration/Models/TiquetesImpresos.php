<?php

namespace App\Modules\Administration\Models;

use Illuminate\Database\Eloquent\Model;

class TiquetesImpresos extends Model
{
    protected $connection = 'mysql-gestion-pasajes';

    protected $table = 'tiquetes_impresos';

    protected $primaryKey = 'IdPrint';

    public $timestamps = false;

    protected $casts = [
        'PrecioBase' => 'float',
        'Descuento' => 'float',
        'PrecioTotal' => 'float',
    ];
}
