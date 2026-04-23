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
        'pull_endpoint',
        'pull_token',
    ];

    protected $hidden = [
        'api_token',
        'pull_token',
    ];

    public function intentos(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(IntentoOperacion::class, 'sistema_id');
    }
}
