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
        'modo_integracion',
        'limite_requests_minuto',
        'pull_endpoint',
        'pull_token',
        'db_conexion',
        'db_tabla',
        'db_filtro_sistema_origen',
        'db_ultima_lectura_at',
    ];

    protected $hidden = [
        'api_token',
        'pull_token',
    ];

    protected function casts(): array
    {
        return [
            'db_ultima_lectura_at' => 'datetime',
        ];
    }

    public function intentos(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(IntentoOperacion::class, 'sistema_id');
    }
}
