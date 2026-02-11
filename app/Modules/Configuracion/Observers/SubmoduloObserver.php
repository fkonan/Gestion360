<?php

namespace App\Modules\Configuracion\Observers;

use App\Modules\Configuracion\Models\SubModulo;
use Illuminate\Support\Facades\Cache;

class SubmoduloObserver
{
    /**
     * Handle the SubModulo "created" event.
     */
    public function created(SubModulo $subModulo): void
    {
        Cache::forget('modulos_con_submodulos');
    }

    /**
     * Handle the SubModulo "updated" event.
     */
    public function updated(SubModulo $subModulo): void
    {
        Cache::forget('modulos_con_submodulos');
    }

    /**
     * Handle the SubModulo "deleted" event.
     */
    public function deleted(SubModulo $subModulo): void
    {
        //
    }

    /**
     * Handle the SubModulo "restored" event.
     */
    public function restored(SubModulo $subModulo): void
    {
        //
    }

    /**
     * Handle the SubModulo "force deleted" event.
     */
    public function forceDeleted(SubModulo $subModulo): void
    {
        //
    }
}
