<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Sarlaft\Http\Requests\Admin\ListaVinculanteRequest;
use App\Modules\Sarlaft\Jobs\SincronizarListaJob;
use App\Modules\Sarlaft\Models\ListaVinculante;
use App\Modules\Sarlaft\Models\SincronizacionLog;
use App\Modules\Sarlaft\Models\SistemaConsumidor;
use App\Modules\Sarlaft\Services\IntentoOperacionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;

class SincronizacionController extends Controller
{
    private const COOLDOWN_SECONDS = 28800;

    public function __construct(
        private readonly IntentoOperacionService $intentoOperacionService,
    ) {}

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
            'puedeSincronizarListasAhora' => $this->puedeSincronizarListasAhora(),
            'puedeSincronizarIntentosAhora' => $this->puedeSincronizarIntentosAhora(),
            'proximaSincronizacionListas' => $this->proximaSincronizacionListas(),
            'proximaSincronizacionIntentos' => $this->proximaSincronizacionIntentos(),
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

    public function sincronizarListasAhora(): RedirectResponse
    {
        if (! $this->puedeSincronizarListasAhora()) {
            return redirect()
                ->route('sarlaft.sincronizacion.index')
                ->with('warning', 'La sincronizacion de listas se ejecuto recientemente. Intenta nuevamente en '.$this->proximaSincronizacionListas().'.');
        }

        $listasActivas = ListaVinculante::query()
            ->where('activa', true)
            ->get();

        if ($listasActivas->isEmpty()) {
            return redirect()
                ->route('sarlaft.sincronizacion.index')
                ->with('warning', 'No hay listas activas para sincronizar.');
        }

        foreach ($listasActivas as $lista) {
            SincronizarListaJob::dispatch($lista);
        }

        $this->marcarSincronizacionListasEjecutada();

        return redirect()
            ->route('sarlaft.sincronizacion.index')
            ->with('success', "Sincronizacion de listas vinculantes enviada a cola para {$listasActivas->count()} lista(s).");
    }

    public function sincronizarIntentosAhora(): RedirectResponse
    {
        if (! $this->puedeSincronizarIntentosAhora()) {
            return redirect()
                ->route('sarlaft.sincronizacion.index')
                ->with('warning', 'La sincronizacion de intentos se ejecuto recientemente. Intenta nuevamente en '.$this->proximaSincronizacionIntentos().'.');
        }

        $sistemas = SistemaConsumidor::query()
            ->where('estado', 'activo')
            ->where(function ($query): void {
                $query->where(function ($pull): void {
                    $pull->where('modo_integracion', 'pull')
                        ->whereNotNull('pull_endpoint');
                })->orWhere(function ($db): void {
                    $db->where('modo_integracion', 'db')
                        ->whereNotNull('db_conexion')
                        ->whereNotNull('db_tabla');
                });
            })
            ->get();

        if ($sistemas->isEmpty()) {
            return redirect()
                ->route('sarlaft.sincronizacion.index')
                ->with('warning', 'No hay sistemas consumidores activos con Pull o lectura de BD configurada.');
        }

        $totalRegistrados = 0;
        foreach ($sistemas as $sistema) {
            $totalRegistrados += $sistema->modo_integracion === 'db'
                ? $this->intentoOperacionService->ejecutarLecturaDb($sistema)
                : $this->intentoOperacionService->ejecutarPull($sistema);
        }

        $this->marcarSincronizacionIntentosEjecutada();

        return redirect()
            ->route('sarlaft.sincronizacion.index')
            ->with('success', "Sincronizacion de intentos ejecutada. Total registrados: {$totalRegistrados} desde {$sistemas->count()} sistema(s).");
    }

    private function puedeSincronizarListasAhora(): bool
    {
        return ! Cache::has($this->cacheKeySincronizacionListas());
    }

    private function puedeSincronizarIntentosAhora(): bool
    {
        return ! Cache::has($this->cacheKeySincronizacionIntentos());
    }

    private function marcarSincronizacionListasEjecutada(): void
    {
        Cache::put(
            $this->cacheKeySincronizacionListas(),
            now()->toDateTimeString(),
            self::COOLDOWN_SECONDS,
        );
    }

    private function marcarSincronizacionIntentosEjecutada(): void
    {
        Cache::put(
            $this->cacheKeySincronizacionIntentos(),
            now()->toDateTimeString(),
            self::COOLDOWN_SECONDS,
        );
    }

    private function proximaSincronizacionListas(): ?string
    {
        return $this->formatearTiempoRestante($this->cacheKeySincronizacionListas());
    }

    private function proximaSincronizacionIntentos(): ?string
    {
        return $this->formatearTiempoRestante($this->cacheKeySincronizacionIntentos());
    }

    private function formatearTiempoRestante(string $cacheKey): ?string
    {
        $ultimaEjecucion = Cache::get($cacheKey);

        if (! $ultimaEjecucion) {
            return null;
        }

        $habilitadoEn = \Carbon\Carbon::parse($ultimaEjecucion)->addSeconds(self::COOLDOWN_SECONDS);

        if ($habilitadoEn->isPast()) {
            return null;
        }

        return $habilitadoEn->diffForHumans(now(), [
            'parts' => 2,
            'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE,
        ]);
    }

    private function cacheKeySincronizacionListas(): string
    {
        return 'sarlaft:sincronizar_listas_ahora';
    }

    private function cacheKeySincronizacionIntentos(): string
    {
        return 'sarlaft:sincronizar_intentos_ahora';
    }
}
