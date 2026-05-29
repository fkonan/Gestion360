<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Alerta extends Model
{
    use SoftDeletes;

    protected $table = 'sarlaft_alertas';

    protected $connection = 'mysql-sarlaft';

    protected $fillable = [
        'intento_id',
        'tipo',
        'nivel_riesgo',
        'estado',
        'tipo_documento',
        'numero_documento',
        'escalada_automatica',
        'escalada_automatica_at',
        'datos_persona',
        'listas_coincidentes',
        'contexto_operacion',
        'evidencias',
        'atendida_por',
        'fecha_atencion',
        'notas',
    ];

    protected function casts(): array
    {
        return [
            'datos_persona' => 'array',
            'listas_coincidentes' => 'array',
            'contexto_operacion' => 'array',
            'evidencias' => 'array',
            'escalada_automatica' => 'boolean',
            'escalada_automatica_at' => 'datetime',
            'fecha_atencion' => 'datetime',
        ];
    }

    public function intento(): BelongsTo
    {
        return $this->belongsTo(IntentoOperacion::class, 'intento_id');
    }

    public function atendidaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atendida_por', 'IdUsuario');
    }
}
