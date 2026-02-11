<?php

namespace App\Modules\PagosRecaudos\Models;

use Illuminate\Database\Eloquent\Model;

class TesCajaTurnos extends Model
{
    protected $connection = 'oracle';

    protected $table = 'TES_CAJASTURNOS';

    protected $primaryKey = 'id';

    public $incrementing = false;

    public $timestamps = false;
}
