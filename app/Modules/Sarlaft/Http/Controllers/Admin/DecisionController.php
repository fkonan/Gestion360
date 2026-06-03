<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Sarlaft\Models\Alerta;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DecisionController extends Controller
{
    /**
     * Listado (reporte) de decisiones de servicio tomadas por el oficial.
     */
    public function index(Request $request): View
    {
        $filtros = $this->filtros($request);
        $decisiones = $this->baseQuery($filtros)
            ->latest('decision_at')
            ->paginate(20)
            ->withQueryString();

        return view('sarlaft::admin.decisiones.index', [
            'decisiones' => $decisiones,
            'filtros' => $filtros,
        ]);
    }

    /**
     * Exporta el reporte de decisiones a CSV respetando los filtros activos.
     */
    public function exportar(Request $request): StreamedResponse
    {
        $filtros = $this->filtros($request);
        $decisiones = $this->baseQuery($filtros)->latest('decision_at')->get();

        $nombre = 'decisiones_sarlaft_'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($decisiones): void {
            $salida = fopen('php://output', 'wb');
            // BOM para que Excel respete UTF-8.
            fwrite($salida, "\xEF\xBB\xBF");
            fputcsv($salida, ['ID', 'Fecha decision', 'Decision', 'Tipo lista', 'Documento', 'Nombre', 'Motivo', 'Oficial']);

            foreach ($decisiones as $alerta) {
                $nombrePersona = $alerta->datos_persona['nombre']
                    ?? trim(($alerta->datos_persona['nombres'] ?? '').' '.($alerta->datos_persona['apellidos'] ?? ''));

                fputcsv($salida, [
                    $alerta->id,
                    $alerta->decision_at?->format('Y-m-d H:i'),
                    $alerta->decision_servicio === 'permitido' ? 'Servicio permitido' : 'Servicio bloqueado',
                    $this->etiquetaTipo($alerta->nivel_riesgo),
                    $alerta->numero_documento,
                    trim((string) $nombrePersona) !== '' ? $nombrePersona : '-',
                    $alerta->notas,
                    $this->nombreOficial($alerta),
                ]);
            }

            fclose($salida);
        }, $nombre, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @param  array<string, mixed>  $filtros
     */
    private function baseQuery(array $filtros): Builder
    {
        return Alerta::query()
            ->with('atendidaPor.persona')
            ->whereNotNull('decision_servicio')
            ->when($filtros['decision'] !== null, fn (Builder $q) => $q->where('decision_servicio', $filtros['decision']))
            ->when($filtros['tipo'] !== null, fn (Builder $q) => $q->where('nivel_riesgo', $filtros['tipo']))
            ->when($filtros['desde'] !== null, fn (Builder $q) => $q->whereDate('decision_at', '>=', $filtros['desde']))
            ->when($filtros['hasta'] !== null, fn (Builder $q) => $q->whereDate('decision_at', '<=', $filtros['hasta']))
            ->when($filtros['busqueda'] !== null, function (Builder $q) use ($filtros): void {
                $like = '%'.$filtros['busqueda'].'%';
                $q->where(function (Builder $sub) use ($like): void {
                    $sub->where('numero_documento', 'like', $like)
                        ->orWhere('datos_persona->nombre', 'like', $like);
                });
            });
    }

    /**
     * @return array<string, mixed>
     */
    private function filtros(Request $request): array
    {
        return [
            'decision' => in_array($request->query('decision'), ['permitido', 'bloqueado'], true) ? $request->query('decision') : null,
            'tipo' => in_array($request->query('tipo'), ['vinculante', 'restrictiva'], true) ? $request->query('tipo') : null,
            'desde' => $request->filled('desde') ? (string) $request->query('desde') : null,
            'hasta' => $request->filled('hasta') ? (string) $request->query('hasta') : null,
            'busqueda' => $request->filled('busqueda') ? trim((string) $request->query('busqueda')) : null,
        ];
    }

    private function etiquetaTipo(?string $nivel): string
    {
        return in_array(strtolower(trim((string) $nivel)), ['vinculante', 'alto'], true)
            ? 'Lista Vinculante'
            : 'Lista Restrictiva';
    }

    private function nombreOficial(Alerta $alerta): string
    {
        $persona = $alerta->atendidaPor?->persona;
        $nombre = trim(($persona?->PerNombres ?? '').' '.($persona?->PerApellidos ?? ''));

        return $nombre !== '' ? $nombre : '-';
    }
}
