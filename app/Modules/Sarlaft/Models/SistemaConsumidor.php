<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SistemaConsumidor extends Model
{
    use SoftDeletes;

    protected $table = 'sarlaft_sistemas_consumidores';

    protected $connection = 'mysql-sarlaft';

    protected $fillable = [
        'nombre',
        'codigo',
        'api_token',
        'estado',
        'limite_requests_minuto',
    ];

    protected $hidden = [
        'api_token',
    ];
}
