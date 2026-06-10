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
        'client_secret',
        'scopes',
        'estado',
        'modo_integracion',
        'limite_requests_minuto',
        'pull_endpoint',
        'pull_token',
        'db_conexion',
        'db_tabla',
        'db_filtro_sistema_origen',
        'db_ultima_lectura_at',
        'db_ultimo_id',
    ];

    protected $hidden = [
        'api_token',
        'pull_token',
        'client_secret',
    ];

    protected function casts(): array
    {
        return [
            'db_ultima_lectura_at' => 'datetime',
            'db_ultimo_id' => 'integer',
        ];
    }

    public function intentos(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(IntentoOperacion::class, 'sistema_id');
    }

    /**
     * Scopes del cliente como arreglo (el campo se guarda separado por espacios).
     *
     * @return array<int, string>
     */
    public function scopesArray(): array
    {
        $scopes = trim((string) $this->scopes);

        if ($scopes === '') {
            return [];
        }

        return collect(preg_split('/\s+/', $scopes) ?: [])
            ->map(static fn (string $scope): string => trim($scope))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
