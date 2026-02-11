<?php

namespace App\Modules\PagosRecaudos\Models;

use Illuminate\Database\Eloquent\Model;

class ConAuxComprobantes extends Model
{
    protected $connection = 'oracle';

    protected $table = 'CON_AUXCOMPROBANTES';

    protected $primaryKey = 'id';

    public $incrementing = false;

    public $timestamps = false;
}
