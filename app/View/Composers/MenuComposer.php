<?php

namespace App\View\Composers;

use App\Models\GESTIONADMIN\Modulo;
use Illuminate\View\View;
use Illuminate\Support\Facades\Cache;

class MenuComposer
{
    public function compose(View $view): void
    {
        $modulos = Cache::remember('modulos_con_submodulos', now()->addHours(4), function () {
            return Modulo::with(['submodulos' => function ($query) {
                $query->where('SubModuloEstado', 'ACTIVO')
                      ->whereNotNull('SubModRuta');
            }])
            ->where('ModuloEstado', 'ACTIVO')
            ->get()
            ->filter(function ($modulo) {
                return $modulo->submodulos->isNotEmpty();
            })
            ->values();
        });

        $view->with('modulos', $modulos);
    }
}
