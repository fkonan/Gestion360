<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Sarlaft\Models\Alerta;
use App\Modules\Sarlaft\Models\ListaNegraInterna;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $stats = [
            'alertas_pendientes' => Alerta::where('estado', 'pendiente')->count(),
            'alertas_en_revision' => Alerta::where('estado', 'en_revision')->count(),
            'lista_negra_total' => ListaNegraInterna::where('estado', 'activo')->count(),
        ];

        $ultimasAlertas = Alerta::with('intento')
            ->where('estado', 'pendiente')
            ->latest()
            ->limit(8)
            ->get();

        return view('sarlaft::admin.dashboard', compact(
            'stats',
            'ultimasAlertas',
        ));
    }
}
