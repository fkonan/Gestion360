<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bloqueo extends Model
{
    use SoftDeletes;

    protected $table = 'sarlaft_bloqueos';

    protected $connection = 'mysql-sarlaft';

    protected $fillable = [
        'tipo_documento',
        'numero_documento',
        'nombre',
        'tipo_bloqueo',
        'estado',
        'motivo_bloqueo',
        'justificacion_desbloqueo',
        'archivo_soporte',
        'creado_por',
    ];

    protected function casts(): array
    {
        return [
            'archivo_soporte' => 'array',
        ];
    }

    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por', 'IdUsuario');
    }
}
