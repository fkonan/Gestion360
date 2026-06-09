<?php

namespace App\Modules\Administration\Models;

use Illuminate\Database\Eloquent\Model;

class Reporteador extends Model
{
    protected $connection = 'mysql-gestion-admin';

    protected $table = 'reporteador';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'nombre',
        'descripcion',
        'sql_base',
        'parametros',
        'max_meses_consulta',
        'origen_db',
        'total_consultas',
    ];

    protected $casts = [
        'max_meses_consulta' => 'integer',
    ];
}
