<?php

namespace App\Models\LOGTRANS;

use App\Models\Huellero\PerIdentHuella;
use App\Models\Huellero\PerPersonasEventos;
use Illuminate\Database\Eloquent\Model;

class PerPersonas extends Model
{
    protected $connection = "oracle";
    protected $table = "PER_PERSONAS";
    protected $primaryKey = "id";
    public $incrementing = false;
    protected $keyType = 'int';

    public $timestamps = false;

    public function PerContratoPersona(){
        return $this->hasOne(PerContratoPersona::class,'pe_id_pe','id')
                ->where('estborrado',0)
                ->where('estado',1);
    }

    /**
     * Relación con las huellas dactilares de la persona
     */
    public function huellas()
    {
        return $this->hasMany(PerIdentHuella::class, 'pe_id', 'id')
                    ->where('estborrado', 0);
    }

    /**
     * Relación con los eventos de entrada/salida de la persona
     */
    public function eventos()
    {
        return $this->hasMany(PerPersonasEventos::class, 'pe_id', 'id')
                    ->where('estborrado', 0);
    }

    /**
     * Obtener solo las huellas activas
     */
    public function huellasActivas()
    {
        return $this->huellas()->activas();
    }

    /**
     * Obtener eventos de hoy
     */
    public function eventosHoy()
    {
        return $this->eventos()->hoy();
    }

    /**
     * Obtener último evento
     */
    public function ultimoEvento()
    {
        return $this->eventos()->orderBy('fechaevento', 'desc')->first();
    }

    /**
     * Verificar si la persona tiene huellas registradas
     */
    public function tieneHuellas(): bool
    {
        return $this->huellas()->count() > 0;
    }

    /**
     * Obtener el estado actual de la persona (dentro/fuera)
     */
    public function getEstadoActualAttribute()
    {
        $ultimoEvento = $this->ultimoEvento();

        if (!$ultimoEvento) {
            return 'Sin registros';
        }

        return $ultimoEvento->esEntrada() ? 'Dentro' : 'Fuera';
    }

    public function nombreCompleto(){
        $nombre = $this->pnombre;
        if ($this->snombre){
            $nombre .= ' ' . $this->snombre;
        }
        $apellido = $this->papellido;
        if ($this->sapellido){
            $apellido .= ' ' . $this->sapellido;
        }
        return mb_strtoupper($nombre . ' ' . $apellido, 'UTF-8');
    }
}
