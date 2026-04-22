<?php

namespace App\Modules\PagosRecaudos\Models;

use Illuminate\Database\Eloquent\Model;

class ConReversoCajasan extends Model
{
    protected $connection = 'oracle';

    protected $table = 'LOGTRANSPRO.CON_REVERSO_CAJASAN';

    protected $primaryKey = 'id';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'id',
        'detalle_id',
        'transmission_datetime',
        'identification_type',
        'identification',
        'amount_tran',
        'state_code',
        'city_code',
        'sequence_id',
        'status',
        'response_code',
        'authorization_rsp_code',
        'error_id',
        'error_message',
        'additional_data',
    ];
}
