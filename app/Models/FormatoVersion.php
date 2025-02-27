<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormatoVersion extends Model
{
    use HasFactory;

    protected $table = "_formatos_versiones";
    protected $primaryKey = "IdFormVer";
    public $timestamps = false;
    
    protected $fillable = [
        "IdFormVer","VerElaboro","VerReviso","VerAprobo","Ruta","VerFecReg",
        "VerHorReg","Version","IdFormato"
    ];

    public function formato(): BelongsTo{
        return $this->belongsTo(Formato::class,'IdFormato','IdFormato');
    }
}
