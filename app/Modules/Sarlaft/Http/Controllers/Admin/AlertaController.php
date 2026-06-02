<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Sarlaft\Http\Requests\Admin\AtenderAlertaRequest;
use App\Modules\Sarlaft\Http\Requests\Admin\FilterAlertasRequest;
use App\Modules\Sarlaft\Http\Requests\Admin\PermitirServicioRequest;
use App\Modules\Sarlaft\Models\Alerta;
use App\Modules\Sarlaft\Services\AlertaEvidenciaService;
use App\Modules\Sarlaft\Services\DecisionServicioService;
use App\Modules\Sarlaft\Services\GestionAlertaService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AlertaController extends Controller
{
    public function __construct(
        private readonly GestionAlertaService $gestionAlertaService,
        private readonly AlertaEvidenciaService $alertaEvidenciaService,
        private readonly DecisionServicioService $decisionServicioService,
    ) {}

    public function index(FilterAlertasRequest $request): View
    {
        $filters = $request->validated();

        $statsBaseQuery = Alerta::query();
        $stats = [
            'coincidencias_pendientes' => (clone $statsBaseQuery)
                ->whereIn('estado', ['pendiente', 'en_revision'])
                ->count(),
            'resueltas_hoy' => (clone $statsBaseQuery)
                ->whereNotNull('fecha_atencion')
                ->whereDate('fecha_atencion', today())
                ->count(),
            'escaladas_automaticas' => (clone $statsBaseQuery)
                ->where('escalada_automatica', true)
                ->count(),
        ];

        $alertas = Alerta::query()
            ->with(['intento.sistema'])
            ->when(isset($filters['search']), function (Builder $query) use ($filters): void {
                $search = (string) $filters['search'];
                $likeSearch = '%'.$search.'%';

                $query->where(function (Builder $searchQuery) use ($likeSearch, $search): void {
                    $searchQuery
                        ->where('tipo', 'like', $likeSearch)
                        ->orWhere('numero_documento', 'like', $likeSearch)
                        ->orWhere('tipo_documento', 'like', $likeSearch)
                        ->orWhere('datos_persona->nombre', 'like', $likeSearch)
                        ->orWhere('datos_persona->nombres', 'like', $likeSearch)
                        ->orWhere('datos_persona->apellidos', 'like', $likeSearch);
                    $searchQuery->orWhereHas('intento', function (Builder $intentoQuery) use ($likeSearch): void {
                        $intentoQuery->where('tipo_operacion', 'like', $likeSearch)
                            ->orWhere('referencia', 'like', $likeSearch)
                            ->orWhere('referencia_externa', 'like', $likeSearch)
                            ->orWhere('tipo_documento', 'like', $likeSearch)
                            ->orWhere('numero_documento', 'like', $likeSearch)
                            ->orWhere('nombre', 'like', $likeSearch)
                            ->orWhere('tipo_lista', 'like', $likeSearch)
                            ->orWhere('lista_nombre', 'like', $likeSearch)
                            ->orWhere('sistema_origen', 'like', $likeSearch);
                    });

                    if (ctype_digit($search)) {
                        $searchQuery->orWhereKey((int) $search);
                    }
                });
            })
            ->when(isset($filters['estado']), function (Builder $query) use ($filters): void {
                $query->where('estado', $filters['estado']);
            })
            ->when(isset($filters['riesgo']), function (Builder $query) use ($filters): void {
                $query->where('nivel_riesgo', $filters['riesgo']);
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('sarlaft::admin.alertas.index', [
            'alertas' => $alertas,
            'stats' => $stats,
            'filters' => $filters,
        ]);
    }

    public function show(Alerta $alerta): View
    {
        $alerta->load([
            'intento.sistema',
            'atendidaPor',
        ]);

        $relacionadasAbiertas = $this->gestionAlertaService->contarRelacionadasAbiertas($alerta);

        return view('sarlaft::admin.alertas.show', compact('alerta', 'relacionadasAbiertas'));
    }

    public function atender(AtenderAlertaRequest $request, Alerta $alerta): RedirectResponse
    {
        $datos = $request->validated();
        $userId = auth()->id() !== null ? (int) auth()->id() : null;

        // Cierre masivo: aplicar estado y notas a todas las alertas abiertas del
        // mismo documento. No procesa evidencias (decision de negocio).
        if ($request->boolean('aplicar_relacionadas')) {
            $afectadas = $this->gestionAlertaService->atenderRelacionadas($alerta, $datos, $userId);

            return redirect()
                ->route('sarlaft.alertas.show', $alerta)
                ->with('success', "Se actualizaron {$afectadas} alerta(s) del documento {$alerta->numero_documento}.");
        }

        $evidenciasGuardadas = [];

        $archivos = $request->file('evidencias', []);
        if (is_array($archivos) && $archivos !== []) {
            $evidenciasGuardadas = $this->alertaEvidenciaService->guardarArchivos(
                $alerta,
                $archivos,
                $userId,
            );
            $datos['evidencias'] = $evidenciasGuardadas;
        }

        try {
            $this->gestionAlertaService->atender(
                $alerta,
                $datos,
                $userId,
            );
        } catch (\Throwable $throwable) {
            if ($evidenciasGuardadas !== []) {
                $this->alertaEvidenciaService->eliminarArchivos($evidenciasGuardadas);
            }

            throw $throwable;
        }

        return redirect()
            ->route('sarlaft.alertas.show', $alerta)
            ->with('success', 'Alerta actualizada correctamente.');
    }

    public function permitirServicio(PermitirServicioRequest $request, Alerta $alerta): RedirectResponse
    {
        $userId = auth()->id() !== null ? (int) auth()->id() : null;
        $motivo = (string) $request->validated()['motivo'];

        $evidenciasGuardadas = [];
        $archivos = $request->file('evidencias', []);
        if (is_array($archivos) && $archivos !== []) {
            $evidenciasGuardadas = $this->alertaEvidenciaService->guardarArchivos(
                $alerta,
                $archivos,
                $userId,
            );
        }

        try {
            $afectados = $this->decisionServicioService->permitirServicio(
                $alerta,
                $motivo,
                $evidenciasGuardadas,
                $userId ?? 0,
            );
        } catch (\Throwable $throwable) {
            if ($evidenciasGuardadas !== []) {
                $this->alertaEvidenciaService->eliminarArchivos($evidenciasGuardadas);
            }

            throw $throwable;
        }

        return redirect()
            ->route('sarlaft.alertas.show', $alerta)
            ->with('success', "Servicio permitido. Se retiraron {$afectados} registro(s) de la lista para el documento {$alerta->numero_documento}.");
    }

    public function descargarEvidencia(Alerta $alerta, string $evidencia): StreamedResponse
    {
        return $this->alertaEvidenciaService->descargar($alerta, $evidencia);
    }
}
