<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Sarlaft\Http\Requests\Admin\AtenderAlertaRequest;
use App\Modules\Sarlaft\Models\Alerta;
use App\Modules\Sarlaft\Services\DecisionServicioService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class AlertaController extends Controller
{
    public function __construct(
        private readonly DecisionServicioService $decisionServicioService,
    ) {}

    public function index(): View
    {
        $alertas = Alerta::with('consulta')
            ->latest()
            ->paginate(20);

        return view('sarlaft::admin.alertas.index', compact('alertas'));
    }

    public function show(Alerta $alerta): View
    {
        $alerta->load(['consulta', 'atendidaPor']);

        return view('sarlaft::admin.alertas.show', compact('alerta'));
    }

    public function atender(AtenderAlertaRequest $request, Alerta $alerta): RedirectResponse
    {
        $datos = $request->validated();

        $this->decisionServicioService->aplicarDecisionEnAtencion($alerta, $datos, (int) auth()->id());

        return redirect()
            ->route('sarlaft.alertas.show', $alerta)
            ->with('success', 'Alerta actualizada correctamente.');
    }
}
