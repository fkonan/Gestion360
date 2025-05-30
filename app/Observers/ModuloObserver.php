<?php

namespace App\Observers;

use App\Models\GESTIONADMIN\Modulo;
use Illuminate\Support\Facades\Cache;

class ModuloObserver
{
    /**
     * Handle the Modulo "created" event.
     */
    public function created(Modulo $modulo): void
    {
        Cache::forget('modulos_con_submodulos');
    }

    /**
     * Handle the Modulo "updated" event.
     */
    public function updated(Modulo $modulo): void
    {
        Cache::forget('modulos_con_submodulos');
    }

    /**
     * Handle the Modulo "deleted" event.
     */
    public function deleted(Modulo $modulo): void
    {
        //
    }

    /**
     * Handle the Modulo "restored" event.
     */
    public function restored(Modulo $modulo): void
    {
        //
    }

    /**
     * Handle the Modulo "force deleted" event.
     */
    public function forceDeleted(Modulo $modulo): void
    {
        //
    }
}
