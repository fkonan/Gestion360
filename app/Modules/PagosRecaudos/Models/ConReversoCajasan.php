<?php

namespace App\Modules\PagosRecaudos\Models;

use Illuminate\Database\Eloquent\Model;

class ConReversoCajasan extends Model
{
    protected $connection = 'oracle';

    protected $table = 'CON_REVERSO_CAJASAN';

    protected $primaryKey = 'id';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'id',
        'detalle_id',
        'transmission_datetime',
        'response_code',
        'authorization_rsp_code',
        'error_id',
        'additional_data',
    ];
}
