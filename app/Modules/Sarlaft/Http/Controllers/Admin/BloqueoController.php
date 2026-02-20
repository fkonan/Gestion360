<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Sarlaft\Events\BloqueoCreado;
use App\Modules\Sarlaft\Http\Requests\Admin\CrearBloqueoRequest;
use App\Modules\Sarlaft\Http\Requests\Admin\DesbloquearRequest;
use App\Modules\Sarlaft\Models\Bloqueo;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class BloqueoController extends Controller
{
    public function index(): View
    {
        $bloqueos = Bloqueo::with('creadoPor')
            ->latest()
            ->paginate(20);

        return view('sarlaft::admin.bloqueos.index', compact('bloqueos'));
    }

    public function create(): View
    {
        return view('sarlaft::admin.bloqueos.create');
    }

    public function store(CrearBloqueoRequest $request): RedirectResponse
    {
        $datos = $request->validated();

        $bloqueo = Bloqueo::create([
            ...$datos,
            'tipo_bloqueo' => 'manual',
            'estado' => 'bloqueado',
            'creado_por' => (int) auth()->id(),
        ]);

        BloqueoCreado::dispatch($bloqueo);

        return redirect()
            ->route('sarlaft.bloqueos.index')
            ->with('success', 'Bloqueo creado correctamente.');
    }

    public function show(Bloqueo $bloqueo): View
    {
        $bloqueo->load('creadoPor');

        return view('sarlaft::admin.bloqueos.show', compact('bloqueo'));
    }

    public function update(DesbloquearRequest $request, Bloqueo $bloqueo): RedirectResponse
    {
        $datos = $request->validated();
        $documentosSoporte = $this->normalizarDocumentosSoporte($datos['documentos_soporte'] ?? []);

        $bloqueo->update([
            'estado' => 'desbloqueado',
            'justificacion_desbloqueo' => $datos['justificacion_desbloqueo'],
            'documentos_soporte' => $documentosSoporte !== [] ? $documentosSoporte : $bloqueo->documentos_soporte,
        ]);

        return redirect()
            ->route('sarlaft.bloqueos.show', $bloqueo)
            ->with('success', 'Bloqueo levantado correctamente.');
    }

    /**
     * @param  array<int, mixed>  $documentosSoporte
     * @return array<int, string>
     */
    private function normalizarDocumentosSoporte(array $documentosSoporte): array
    {
        return array_values(array_filter(array_map(static function (mixed $documento): string {
            return trim((string) $documento);
        }, $documentosSoporte), static function (string $documento): bool {
            return $documento !== '';
        }));
    }
}
