<?php

namespace App\View\Composers;

use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use App\Models\Modulo;

class MenuComposer
{
    public function compose(View $view): void
    {

        $modulos = Modulo::with('submodulos')
            ->whereNull('Mod_Padre_Id')
            ->where('ModEstado', 'ACTIVO')
            ->get();

        $view->with('modulos', $modulos);
    }
}
