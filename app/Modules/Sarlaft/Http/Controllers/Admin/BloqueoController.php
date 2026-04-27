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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

        $archivoSoporte = null;

        if ($request->hasFile('archivo_soporte')) {
            $archivo = $request->file('archivo_soporte');
            $archivoId = (string) Str::uuid();
            $extension = strtolower($archivo->getClientOriginalExtension());
            $nombreAlmacenado = $archivoId.($extension !== '' ? '.'.$extension : '');
            $ruta = $archivo->storeAs('sarlaft/bloqueos/'.$bloqueo->id.'/soporte', $nombreAlmacenado, 'local');

            $archivoSoporte = [
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

        $bloqueo->update([
            'estado' => 'desbloqueado',
            'justificacion_desbloqueo' => $datos['justificacion_desbloqueo'],
            'archivo_soporte' => $archivoSoporte ?? $bloqueo->archivo_soporte,
        ]);

        return redirect()
            ->route('sarlaft.bloqueos.show', $bloqueo)
            ->with('success', 'Bloqueo levantado correctamente.');
    }

    public function descargarArchivo(Bloqueo $bloqueo): StreamedResponse
    {
        $archivo = $bloqueo->archivo_soporte;

        abort_if($archivo === null || ! is_array($archivo), 404);

        $disk = isset($archivo['disk']) && is_string($archivo['disk']) ? $archivo['disk'] : 'local';
        $ruta = isset($archivo['path']) && is_string($archivo['path']) ? $archivo['path'] : null;

        abort_if($ruta === null || ! Storage::disk($disk)->exists($ruta), 404);

        $nombreDescarga = isset($archivo['original_name']) && is_string($archivo['original_name'])
            ? $archivo['original_name']
            : basename($ruta);

        return Storage::disk($disk)->download($ruta, $nombreDescarga);
    }
}
