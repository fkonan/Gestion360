<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Sarlaft\Http\Requests\Admin\ListaVinculanteRequest;
use App\Modules\Sarlaft\Jobs\SincronizarListaJob;
use App\Modules\Sarlaft\Models\ListaVinculante;
use App\Modules\Sarlaft\Models\SincronizacionLog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;

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

        return view('sarlaft::admin.sincronizacion.index', [
            'logs' => $logs,
            'listas' => $listas,
            'isDev' => app()->environment(['local', 'development', 'dev']),
            'puedeSincronizarAhora' => $this->puedeSincronizarAhora(),
        ]);
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

    public function sincronizarAhora(): RedirectResponse
    {
        if (! app()->environment(['local', 'development', 'dev'])) {
            return redirect()
                ->route('sarlaft.sincronizacion.index')
                ->with('error', 'Esta accion solo esta habilitada en entorno dev.');
        }

        if (! $this->puedeSincronizarAhora()) {
            return redirect()
                ->route('sarlaft.sincronizacion.index')
                ->with('warning', 'La sincronizacion manual ya se ejecuto hoy. Intenta nuevamente manana.');
        }

        $listasActivas = ListaVinculante::query()
            ->where('activa', true)
            ->get();

        foreach ($listasActivas as $lista) {
            SincronizarListaJob::dispatch($lista);
        }

        $this->marcarSincronizacionAhoraEjecutada();

        return redirect()
            ->route('sarlaft.sincronizacion.index')
            ->with('success', "Sincronizacion manual enviada a cola para {$listasActivas->count()} listas.");
    }

    private function puedeSincronizarAhora(): bool
    {
        return ! Cache::has($this->cacheKeySincronizacionAhoraDiaria());
    }

    private function marcarSincronizacionAhoraEjecutada(): void
    {
        $expiraEnSegundos = now()->endOfDay()->diffInSeconds(now());
        Cache::put(
            $this->cacheKeySincronizacionAhoraDiaria(),
            now()->toDateTimeString(),
            max($expiraEnSegundos, 60),
        );
    }

    private function cacheKeySincronizacionAhoraDiaria(): string
    {
        return 'sarlaft:sincronizar_ahora:'.now()->toDateString();
    }
}
