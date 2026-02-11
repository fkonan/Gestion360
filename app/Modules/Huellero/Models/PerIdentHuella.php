<?php

namespace App\Modules\Huellero\Models;

use App\Modules\GestionRRHH\Models\PerPersonas;
use Illuminate\Database\Eloquent\Model;

class PerIdentHuella extends Model
{
    protected $connection = 'oracle';

    protected $table = 'PER_IDENTHUELLA';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'pe_id',
        'dedo',
        'huella',
        'feccaptura',
        'feccreacion',
        'fecmodifica',
        'usrcreacion',
        'usrmodifica',
        'rolmodifica',
        'empcreacion',
        'empmodifica',
        'estborrado',
    ];

    protected $casts = [
        'feccaptura' => 'datetime',
        'feccreacion' => 'datetime',
        'fecmodifica' => 'datetime',
        'estborrado' => 'integer',
    ];

    public const DEDOS = [
        '01' => 'Pulgar derecho',
        '02' => 'Índice derecho',
        '03' => 'Medio derecho',
        '04' => 'Anular derecho',
        '05' => 'Menique derecho',
        '06' => 'Pulgar izquierdo',
        '07' => 'Indice izquierdo',
        '08' => 'Medio izquierdo',
        '09' => 'Anular izquierdo',
        '10' => 'Menique izquierdo',
    ];

    public static function dedosDisponibles(): array
    {
        return self::DEDOS;
    }

    /**
     * Relación con la tabla de personas
     */
    public function persona()
    {
        return $this->belongsTo(PerPersonas::class, 'pe_id', 'id');
    }

    /**
     * Scope para obtener solo huellas activas
     */
    public function scopeActivas($query)
    {
        return $query->where('estborrado', 0);
    }

    /**
     * Scope para obtener huellas por dedo específico
     */
    public function scopePorDedo($query, $dedo)
    {
        return $query->where('dedo', $dedo);
    }

    /**
     * Obtener el nombre del dedo basado en el código
     */
    public function getNombreDedeAttribute()
    {
        return self::DEDOS[$this->dedo] ?? 'Dedo desconocido';
    }

    /**
     * Verificar si la huella está activa
     */
    public function estaActiva(): bool
    {
        return $this->estborrado == 0;
    }

    /**
     * Obtener la plantilla de huella decodificada
     */
    public function getHuellaDecodificada()
    {
        try {
            return base64_decode($this->huella);
        } catch (\Exception $e) {
            return null;
        }
    }
}
