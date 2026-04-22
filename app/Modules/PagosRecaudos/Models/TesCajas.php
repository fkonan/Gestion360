<?php

namespace App\Modules\PagosRecaudos\Models;

use Illuminate\Database\Eloquent\Model;

class TesCajas extends Model
{
    protected $connection = 'oracle';

    protected $table = 'TES_CAJAS';

    protected $primaryKey = 'id';

    public $incrementing = false;

    public $timestamps = false;
}
