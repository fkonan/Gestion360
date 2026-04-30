<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Models;

use Illuminate\Database\Eloquent\Model;

class NovedadExportacion extends Model
{
    public $timestamps = false;

    protected $table = 'sarlaft_novedades_exportacion';

    protected $connection = 'mysql-sarlaft';

    protected $fillable = [
        'origen_lista',
        'tipo_novedad',
        'registro_lista_id',
        'lista_negra_id',
        'sincronizacion_log_id',
        'lista_id',
        'nombre_lista',
        'datos',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'datos' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
