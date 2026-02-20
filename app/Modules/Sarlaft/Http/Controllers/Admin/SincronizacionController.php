<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Sarlaft\Http\Requests\Admin\ListaVinculanteRequest;
use App\Modules\Sarlaft\Models\ListaVinculante;
use App\Modules\Sarlaft\Models\SincronizacionLog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class SincronizacionController extends Controller
{
    public function index(): View
    {
        $listas = ListaVinculante::query()
            ->orderByDesc('activa')
            ->orderBy('nombre')
            ->get();

        $logs = SincronizacionLog::with('lista')
            ->latest('created_at')
            ->paginate(20);

        return view('sarlaft::admin.sincronizacion.index', compact('logs', 'listas'));
    }

    public function storeLista(ListaVinculanteRequest $request): RedirectResponse
    {
        $datos = $request->validated();
        $datos['activa'] = $request->boolean('activa', true);

        ListaVinculante::create($datos);

        return redirect()
            ->route('sarlaft.sincronizacion.index')
            ->with('success', 'Lista vinculante creada correctamente.');
    }

    public function sincronizarDesdeConfig(): RedirectResponse
    {
        $listasConfig = collect(config('listas'))
            ->filter(static function (mixed $item): bool {
                return is_array($item)
                    && isset($item['nombre'], $item['url'], $item['parser']);
            });

        $sincronizadas = 0;

        foreach ($listasConfig as $configLista) {
            $lista = ListaVinculante::withTrashed()
                ->whereRaw('LOWER(nombre) = ?', [mb_strtolower((string) $configLista['nombre'])])
                ->first();

            $payload = [
                'nombre' => $configLista['nombre'],
                'tipo' => 'vinculante',
                'url_fuente' => $configLista['url'],
                'frecuencia_sync' => $configLista['frecuencia'] ?? 'diaria',
                'activa' => true,
            ];

            if ($lista) {
                $lista->fill($payload);
                if ($lista->trashed()) {
                    $lista->restore();
                }
                $lista->save();
            } else {
                ListaVinculante::create($payload);
            }

            $sincronizadas++;
        }

        return redirect()
            ->route('sarlaft.sincronizacion.index')
            ->with('success', "Listas sincronizadas desde config: {$sincronizadas}.");
    }
}
