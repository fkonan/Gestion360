<?php

namespace App\Modules\PagosRecaudos\Models;

use Illuminate\Database\Eloquent\Model;

class ConPagosRecaudos extends Model
{
    protected $connection = 'oracle';

    protected $table = 'CON_CARGUEPAGOSYRECAUDOS';

    protected $primaryKey = 'id';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'id',
        'idenempresa',
        'descripcion',
        'valortotal',
        'tipomovimiento',
        'estado',
        'fechacargue',
        'usrcargue',
        'estborrado',
        'fecmodifica',
        'empmodifica',
        'usrmodifica',
        'rolmodifica',
        'feccreacion',
        'usrcreacion',
        'empcreacion',
    ];
}
