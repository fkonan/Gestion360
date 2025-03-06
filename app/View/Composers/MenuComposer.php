<?php

namespace App\View\Composers;

use Illuminate\View\View;
use App\Models\Modulo;

class MenuComposer
{
    public function compose(View $view): void
    {
        $modulos = Modulo::with('submodulos')->whereNull('Mod_Padre_Id')->get();
        $view->with('modulos', $modulos);
    }
}
