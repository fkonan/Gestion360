<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SincronizacionLog extends Model
{
    public $timestamps = false;

    protected $table = 'sarlaft_sincronizacion_logs';

    protected $connection = 'mysql-sarlaft';

    protected $fillable = [
        'lista_id',
        'estado',
        'registros_procesados',
        'registros_nuevos',
        'registros_actualizados',
        'registros_eliminados',
        'error_mensaje',
        'duracion_segundos',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function lista(): BelongsTo
    {
        return $this->belongsTo(ListaVinculante::class, 'lista_id');
    }

    public function registrosAfectados(): HasMany
    {
        return $this->hasMany(RegistroLista::class, 'sincronizacion_log_id');
    }
}
