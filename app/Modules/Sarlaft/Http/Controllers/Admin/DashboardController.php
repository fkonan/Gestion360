<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Sarlaft\Models\Alerta;
use App\Modules\Sarlaft\Models\Consulta;
use App\Modules\Sarlaft\Models\ListaNegraInterna;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $stats = [
            'consultas_hoy' => Consulta::whereDate('created_at', today())->count(),
            'consultas_mes' => Consulta::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
            'alertas_pendientes' => Alerta::where('estado', 'pendiente')->count(),
            'alertas_en_revision' => Alerta::where('estado', 'en_revision')->count(),
            'lista_negra_total' => ListaNegraInterna::where('estado', 'activo')->count(),
        ];

        $ultimasAlertas = Alerta::with('consulta')
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
