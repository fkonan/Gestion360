<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RegistroLista extends Model
{
    use SoftDeletes;

    protected $table = 'sarlaft_registros_lista';

    protected $connection = 'mysql-sarlaft';

    protected $fillable = [
        'lista_id',
        'tipo_entidad',
        'identificacion',
        'tipo_identificacion',
        'nombres',
        'alias',
        'fecha_nacimiento',
        'pais',
        'motivo',
        'fecha_inclusion',
        'referencia_externa',
        'estado',
        'novedad',
        'sincronizacion_log_id',
    ];

    protected function casts(): array
    {
        return [
            'alias' => 'array',
            'fecha_nacimiento' => 'date',
            'fecha_inclusion' => 'date',
        ];
    }

    public function lista(): BelongsTo
    {
        return $this->belongsTo(ListaVinculante::class, 'lista_id');
    }

    public function sincronizacionLog(): BelongsTo
    {
        return $this->belongsTo(SincronizacionLog::class, 'sincronizacion_log_id');
    }
}
