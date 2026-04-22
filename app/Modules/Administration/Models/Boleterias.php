<?php

namespace App\Modules\Administration\Models;

use App\Modules\GestionRRHH\Models\PePersonalEstados;
use Illuminate\Database\Eloquent\Model;

class Boleterias extends Model
{
    protected $connection = 'sqlsrv';

    protected $table = 'Boleterias';

    protected $primaryKey = 'Id';

    public $incrementing = false;

    public $timestamps = false;

    public function bloqueos()
    {
        return $this->hasMany(PePersonalEstados::class, 'PersonalID', 'Id');
    }
}
