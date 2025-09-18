<?php

namespace App\Observers;

use App\Models\GESTIONADMIN\Modulo;
use Illuminate\Support\Facades\Cache;

class ModuloObserver
{
  public function created(Modulo $modulo): void
  {
    Cache::forget('modulos_con_submodulos');
  }

  public function updated(Modulo $modulo): void
  {
    Cache::forget('modulos_con_submodulos');
  }
}
