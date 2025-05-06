<?php

namespace App\View\Composers;

use App\Models\GESTIONADMIN\Modulo;
use Illuminate\View\View;

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
