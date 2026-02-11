<?php

namespace App\Modules\GestionRRHH\Models;

use Illuminate\Database\Eloquent\Model;

class PerPersonaBloqueo extends Model
{
    protected $connection = 'oracle';

    protected $table = 'PER_PERSONASBLOQUEO';

    protected $primaryKey = 'id';

    public $incrementing = false;

    public $timestamps = false;
}
