<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Incapacidad extends Model
{
    protected $connection = 'mysql-gestion-humana';
    protected $table = "incapacidades";
    protected $primaryKey = "IdIncapacidad";

    public $timestamps = false;

    protected $fillable = [
        'IdIncapacidad',
        'IncPerNom',
        'PerNumDoc',
        'IdPerOracle',
        'CausaId',
        'Diagnostico',
        'EPSId',
        'ARLId',
        'IncTipo',
        'IncapacidadEstado',
        'Observacion',
        'RevisionDatos',
        'IncFecIni',
        'IncFecFin',
        'IncFechReg',
        'IncHorReg'
       
    ];

    public function causa(): BelongsTo{
        return $this->belongsTo(Parametros::class,'CausaId','IdParametro')
            ->where('ParNomGru','CAUSA-INCAPACIDAD');
    }

    public function diagnostico(): BelongsTo{
        return $this->belongsTo(Enfermedades::class,'Diagnostico','IdEnfermedad')
            ->select('IdEnfermedad','DescCie','CodigoCie');
    }

    public function eps(): BelongsTo{
        return $this->belongsTo(Eps::class,'EPSId','IdEPS')
            ->select('IdEPS','EPSNombre');
    }

    public function arl(): BelongsTo{
        return $this->belongsTo(Arl::class,'ARLId','IdARL')
            ->select('IdARL','ARLNombre');
    }

    public function seguimiento(): HasMany{
        return $this->hasMany(incapacidadesSeguimiento::class, 'IncapacidadId', 'IdIncapacidad');
    }

    public function documentos(): HasMany{
        return $this->hasMany(IncapacidadesDocumentos::class, 'IncapacidadId', 'IdIncapacidad');
    }
}
