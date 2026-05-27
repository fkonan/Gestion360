<?php

namespace App\Modules\Huellero\Models;

use App\Modules\GestionRRHH\Models\PerPersonas;
use Illuminate\Database\Eloquent\Model;

class PerPersonasEventos extends Model
{
    protected $connection = 'oracle-pruebas';

    protected $table = 'PER_PERSONASEVENTOS';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'pe_id',
        'fechaevento',
        'evento',
        'tiporegistro',
        'anotacion',
        'observacion',
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
        'fechaevento' => 'datetime',
        'feccreacion' => 'datetime',
        'fecmodifica' => 'datetime',
        'tiporegistro' => 'integer',
        'estborrado' => 'integer',
    ];

    // Constantes para eventos
    const EVENTO_ENTRADA = '49';

    const EVENTO_SALIDA = '50';

    // Constantes para tipos de registro
    const TIPO_AUTOMATICO = 0;

    const TIPO_MANUAL = 1;

    /**
     * Relación con la tabla de personas
     */
    public function persona()
    {
        return $this->belongsTo(PerPersonas::class, 'pe_id', 'id');
    }

    /**
     * Scope para obtener solo eventos activos
     */
    public function scopeActivos($query)
    {
        return $query->where('estborrado', 0);
    }

    /**
     * Scope para eventos automáticos
     */
    public function scopeAutomaticos($query)
    {
        return $query->where('tiporegistro', self::TIPO_AUTOMATICO);
    }

    /**
     * Scope para eventos manuales
     */
    public function scopeManuales($query)
    {
        return $query->where('tiporegistro', self::TIPO_MANUAL);
    }

    /**
     * Scope para entradas
     */
    public function scopeEntradas($query)
    {
        return $query->where('evento', self::EVENTO_ENTRADA);
    }

    /**
     * Scope para salidas
     */
    public function scopeSalidas($query)
    {
        return $query->where('evento', self::EVENTO_SALIDA);
    }

    /**
     * Scope para eventos de hoy
     */
    public function scopeHoy($query)
    {
        return $query->whereDate('fechaevento', today());
    }

    /**
     * Accessor para obtener el tipo de evento en texto
     */
    public function getTipoEventoAttribute()
    {
        return $this->evento === self::EVENTO_ENTRADA ? 'Entrada' : 'Salida';
    }

    /**
     * Accessor para obtener el tipo de registro en texto
     */
    public function getTipoRegistroTextoAttribute()
    {
        return $this->tiporegistro === self::TIPO_AUTOMATICO ? 'Automático' : 'Manual';
    }

    /**
     * Verificar si es un evento de entrada
     */
    public function esEntrada(): bool
    {
        return $this->evento === self::EVENTO_ENTRADA;
    }

    /**
     * Verificar si es un evento de salida
     */
    public function esSalida(): bool
    {
        return $this->evento === self::EVENTO_SALIDA;
    }

    /**
     * Verificar si es un registro automático
     */
    public function esAutomatico(): bool
    {
        return $this->tiporegistro === self::TIPO_AUTOMATICO;
    }

    /**
     * Verificar si es un registro manual
     */
    public function esManual(): bool
    {
        return $this->tiporegistro === self::TIPO_MANUAL;
    }

    /**
     * Obtener el icono CSS para el tipo de evento
     */
    public function getIconoEventoAttribute()
    {
        return $this->esEntrada() ? 'bi-box-arrow-in-right text-success' : 'bi-box-arrow-right text-warning';
    }

    /**
     * Obtener la clase CSS para el tipo de registro
     */
    public function getClaseRegistroAttribute()
    {
        return $this->esAutomatico() ? 'badge bg-success' : 'badge bg-warning text-dark';
    }

    /**
     * Obtener el mensaje de error formateado para registros manuales
     */
    public function getMensajeErrorAttribute()
    {
        if ($this->esAutomatico() || ! $this->observacion) {
            return null;
        }

        // Extraer el tipo de error de la observación
        $observacion = $this->observacion;

        if (str_contains($observacion, 'no hubo sistema')) {
            return 'Sistema no disponible';
        }

        if (str_contains($observacion, 'huellero no marco') || str_contains($observacion, 'NO LEYO HUELLA')) {
            return 'Error en lectura de huella';
        }

        if (str_contains($observacion, 'ERROR EN EL LECTOR') || str_contains($observacion, 'error de lector')) {
            return 'Falla en el lector';
        }

        if (str_contains($observacion, 'falla de comunicacion')) {
            return 'Error de comunicación';
        }

        if (str_contains($observacion, 'Error de sistema') || str_contains($observacion, 'fallas en el sistema')) {
            return 'Error del sistema';
        }

        return 'Error no clasificado';
    }
}
