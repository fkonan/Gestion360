<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Sarlaft\Http\Requests\Admin\ListaNegraRequest;
use App\Modules\Sarlaft\Models\ListaNegraInterna;
use App\Modules\Sarlaft\Services\NovedadExportacionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListaNegraController extends Controller
{
    public function __construct(
        private readonly NovedadExportacionService $novedadExportacionService,
    ) {}

    public function index(): View
    {
        $registros = ListaNegraInterna::with(['creadoPor', 'retiradoPor'])
            ->latest()
            ->paginate(20);

        return view('sarlaft::admin.lista-negra.index', compact('registros'));
    }

    public function create(): View
    {
        return view('sarlaft::admin.lista-negra.create');
    }

    public function store(ListaNegraRequest $request): RedirectResponse
    {
        $datos = $request->validated();
        unset($datos['archivo_evidencia_inclusion'], $datos['archivo_evidencia_retiro']);

        $evidenciaInclusion = $this->guardarEvidenciaListaNegra($request->file('archivo_evidencia_inclusion'));

        $registro = ListaNegraInterna::create([
            ...$datos,
            'evidencia_inclusion' => $evidenciaInclusion,
            'estado' => 'activo',
            'creado_por' => (int) auth()->id(),
        ]);

        $this->novedadExportacionService->registrarNovedadInterna(
            registro: $registro,
            tipoNovedad: 'ingreso',
        );

        return redirect()
            ->route('sarlaft.lista-negra.index')
            ->with('success', 'Registro agregado a la lista negra.');
    }

    public function show(ListaNegraInterna $listaNegra): View
    {
        $listaNegra->load(['creadoPor', 'retiradoPor']);

        return view('sarlaft::admin.lista-negra.show', compact('listaNegra'));
    }

    public function edit(ListaNegraInterna $listaNegra): View
    {
        return view('sarlaft::admin.lista-negra.edit', compact('listaNegra'));
    }

    public function update(ListaNegraRequest $request, ListaNegraInterna $listaNegra): RedirectResponse
    {
        $datos = $request->validated();
        unset($datos['archivo_evidencia_inclusion'], $datos['archivo_evidencia_retiro']);

        $estadoAnterior = (string) $listaNegra->estado;

        if ($request->hasFile('archivo_evidencia_inclusion')) {
            $datos['evidencia_inclusion'] = $this->guardarEvidenciaListaNegra($request->file('archivo_evidencia_inclusion'));
        }

        if (($datos['estado'] ?? $listaNegra->estado) === 'inactivo') {
            $datos['motivo_retiro'] = $datos['motivo_retiro'];
            $evidenciaRetiroNueva = $this->guardarEvidenciaListaNegra($request->file('archivo_evidencia_retiro'));
            $datos['evidencia_retiro'] = $evidenciaRetiroNueva ?? $listaNegra->evidencia_retiro;
            $datos['retirado_por'] = (int) auth()->id();
            $datos['retirado_at'] = now();
        }

        if (($datos['estado'] ?? $listaNegra->estado) === 'activo' && $estadoAnterior === 'inactivo') {
            $datos['motivo_retiro'] = null;
            $datos['evidencia_retiro'] = null;
            $datos['retirado_por'] = null;
            $datos['retirado_at'] = null;
        }

        $listaNegra->update($datos);
        $listaNegra->refresh();

        if ($estadoAnterior !== $listaNegra->estado && $listaNegra->estado === 'inactivo') {
            $this->novedadExportacionService->registrarNovedadInterna(
                registro: $listaNegra,
                tipoNovedad: 'salida',
            );
        } elseif ($estadoAnterior !== $listaNegra->estado && $listaNegra->estado === 'activo') {
            $this->novedadExportacionService->registrarNovedadInterna(
                registro: $listaNegra,
                tipoNovedad: 'ingreso',
            );
        } else {
            $this->novedadExportacionService->registrarNovedadInterna(
                registro: $listaNegra,
                tipoNovedad: 'actualizado',
            );
        }

        return redirect()
            ->route('sarlaft.lista-negra.show', $listaNegra)
            ->with('success', 'Registro actualizado correctamente.');
    }

    public function destroy(ListaNegraInterna $listaNegra): RedirectResponse
    {
        return redirect()
            ->route('sarlaft.lista-negra.index')
            ->with('error', 'La eliminacion directa esta deshabilitada. Usa editar para inactivar con motivo y evidencia.');
    }

    public function descargarEvidencia(ListaNegraInterna $listaNegra, string $tipo): StreamedResponse
    {
        $campo = $tipo === 'retiro' ? 'evidencia_retiro' : 'evidencia_inclusion';
        $archivo = $listaNegra->getAttribute($campo);

        abort_if(! is_array($archivo), 404);

        $disk = isset($archivo['disk']) && is_string($archivo['disk']) ? $archivo['disk'] : 'local';
        $path = isset($archivo['path']) && is_string($archivo['path']) ? $archivo['path'] : '';

        abort_if($path === '' || ! Storage::disk($disk)->exists($path), 404);

        $nombre = isset($archivo['original_name']) && is_string($archivo['original_name'])
            ? $archivo['original_name']
            : basename($path);

        return Storage::disk($disk)->download($path, $nombre);
    }

    private function guardarEvidenciaListaNegra(?\Illuminate\Http\UploadedFile $archivo): ?array
    {
        if ($archivo === null) {
            return null;
        }

        $archivoId = (string) Str::uuid();
        $extension = strtolower((string) $archivo->getClientOriginalExtension());
        $nombreAlmacenado = $archivoId.($extension !== '' ? '.'.$extension : '');
        $ruta = $archivo->storeAs('sarlaft/lista-negra/evidencias', $nombreAlmacenado, 'local');

        return [
            'id' => $archivoId,
            'disk' => 'local',
            'path' => $ruta,
            'original_name' => $archivo->getClientOriginalName(),
            'mime_type' => $archivo->getClientMimeType(),
            'size_bytes' => (int) ($archivo->getSize() ?? 0),
            'uploaded_by' => (int) auth()->id(),
            'uploaded_at' => now()->toIso8601String(),
        ];
    }
}
