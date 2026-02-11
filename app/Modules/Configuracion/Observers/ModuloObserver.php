<?php

namespace App\Modules\Configuracion\Observers;

use App\Modules\Configuracion\Models\Modulo;
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
