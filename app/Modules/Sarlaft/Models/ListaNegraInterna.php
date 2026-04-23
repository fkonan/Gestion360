<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ListaNegraInterna extends Model
{
    use SoftDeletes;

    protected $table = 'sarlaft_lista_negra_interna';

    protected $connection = 'mysql-sarlaft';

    protected $fillable = [
        'tipo_entidad',
        'tipo_documento',
        'numero_documento',
        'nombres',
        'motivo',
        'evidencia_inclusion',
        'motivo_retiro',
        'evidencia_retiro',
        'creado_por',
        'retirado_por',
        'retirado_at',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'evidencia_inclusion' => 'array',
            'evidencia_retiro' => 'array',
            'retirado_at' => 'datetime',
        ];
    }

    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por', 'IdUsuario');
    }

    public function retiradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'retirado_por', 'IdUsuario');
    }
}
