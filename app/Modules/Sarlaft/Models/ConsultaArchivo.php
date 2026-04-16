<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Models;

use Illuminate\Database\Eloquent\Model;

class ConsultaArchivo extends Model
{
    public $timestamps = false;

    protected $table = 'sarlaft_consultas_archivo';

    protected $connection = 'mysql-sarlaft';

    protected $fillable = [
        'consulta_id_original',
        'sistema_origen',
        'tipo_documento',
        'numero_documento',
        'nombre_consultado',
        'encontrado',
        'presta_servicio',
        'nivel_riesgo',
        'coincidencias',
        'contexto_operacion',
        'ip_origen',
        'created_at',
        'archived_at',
        'motivo_archivo',
    ];

    protected function casts(): array
    {
        return [
            'encontrado' => 'boolean',
            'presta_servicio' => 'boolean',
            'coincidencias' => 'array',
            'contexto_operacion' => 'array',
            'created_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }
}
