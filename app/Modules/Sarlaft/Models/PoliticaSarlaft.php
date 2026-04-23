<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Models;

use Illuminate\Database\Eloquent\Model;

class PoliticaSarlaft extends Model
{
    protected $table = 'sarlaft_politicas';

    protected $connection = 'mysql-sarlaft';

    protected $fillable = [
        'id',
        'sla_dias_alerta',
        'auto_escalar_riesgos',
        'auto_estado',
        'auto_atender_lista_negra_interna',
        'auto_crear_alerta_atendida',
        'auto_user_id',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'sla_dias_alerta' => 'integer',
            'auto_escalar_riesgos' => 'array',
            'auto_atender_lista_negra_interna' => 'boolean',
            'auto_crear_alerta_atendida' => 'boolean',
            'auto_user_id' => 'integer',
            'updated_by' => 'integer',
        ];
    }
}
