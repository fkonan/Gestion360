<?php

namespace App\View\Composers;

use App\Models\GESTIONADMIN\Modulo;
use Illuminate\View\View;

class MenuComposer
{
    public function compose(View $view): void
    {
        $modulos = Modulo::with(['submodulos' => function ($query) {
                $query->where('SubModuloEstado', 'ACTIVO');
            }])
            ->where('ModuloEstado', 'ACTIVO')
            ->get();

        $view->with('modulos', $modulos);
    }
}
