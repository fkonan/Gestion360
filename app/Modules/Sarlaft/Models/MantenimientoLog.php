<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Models;

use Illuminate\Database\Eloquent\Model;

class MantenimientoLog extends Model
{
    public $timestamps = false;

    protected $table = 'sarlaft_mantenimiento_logs';

    protected $connection = 'mysql-sarlaft';

    protected $fillable = [
        'proceso',
        'estado',
        'registros_evaluados',
        'registros_procesados',
        'registros_omitidos',
        'duracion_segundos',
        'error_mensaje',
        'detalles',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'detalles' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
