<?php

namespace App\Modules\GestionRRHH\Models;

use Illuminate\Database\Eloquent\Model;

class PePersonalEstados extends Model
{
    protected $connection = 'sqlsrv';

    protected $table = 'PE_PersonalEstados';

    protected $primaryKey = 'PersonalEstadoID';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'FechaFinalizacion',
    ];
}
