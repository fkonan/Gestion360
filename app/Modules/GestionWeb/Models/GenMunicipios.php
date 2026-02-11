<?php

namespace App\Modules\GestionWeb\Models;

use Illuminate\Database\Eloquent\Model;

class GenMunicipios extends Model
{
    protected $connection = 'oracle';

    protected $table = 'GEN_MUNICIPIOS';

    protected $primaryKey = 'codigo';
}
