<?php

namespace App\Models\GESTIONHUMANA;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enfermedades extends Model
{
  protected $connection = 'mysql-gestion-humana';
  protected $table = "Enfermedades";
  protected $primaryKey = "IdEnfermedad";

  public $timestamps = false;

  public function usuarioRegistro(): BelongsTo
  {
    return $this->belongsTo(User::class, 'IdUserReg', 'IdUsuario');
  }
}
