<?php

namespace App\Modules\RadFact\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\RadFact\Models\RadFactRadicacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubgerenciaComprasController extends Controller
{
    /**
     * Listar radicaciones pendientes para subgerencia
     */
    public function indexSubgerencia()
    {
        try {
            $radicaciones = RadFactRadicacion::pendienteSubgerencia()
                ->with(['proveedor', 'usuario', 'distribucionesActivas.area'])
                ->latest('fecha_envio_subgerencia')
                ->paginate(20);

            return view('radfact::subgerencia.index', compact('radicaciones'));
        } catch (\Exception $e) {
            Log::error('Error listando radicaciones subgerencia', ['error' => $e->getMessage()]);

            return back()->with('error', 'Error al cargar radicaciones');
        }
    }

    /**
     * Mostrar detalle para subgerencia
     */
    public function showSubgerencia(RadFactRadicacion $radicacion)
    {
        $radicacion->load([
            'proveedor',
            'usuario',
            'distribucionesActivas.area',
            'distribucionesActivas.aprobacion.usuario',
        ]);

        return view('radfact::subgerencia.show', compact('radicacion'));
    }

    /**
     * Aprobar en subgerencia
     */
    public function aprobarSubgerencia(Request $request, RadFactRadicacion $radicacion)
    {
        $validated = $request->validate([
            'observacion' => 'nullable|string|max:1000',
        ]);

        DB::beginTransaction();
        try {
            $radicacion->update([
                'estado_subgerencia' => 'APROBADO',
                'observacion_subgerencia' => $validated['observacion'] ?? null,
                'usuario_subgerencia_id' => auth()->user()->IdUsuario,
                'fecha_respuesta_subgerencia' => now(),
                // Pasar a compras
                'estado' => 'PENDIENTE_COMPRAS',
                'fecha_envio_compras' => now(),
                'estado_compras' => 'PENDIENTE',
            ]);

            DB::commit();

            // TODO: Enviar correo a compras

            return redirect()->route('radfact.subgerencia.index')
                ->with('success', 'Radicación aprobada y enviada a compras');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error aprobando en subgerencia', ['error' => $e->getMessage()]);

            return back()->with('error', 'Error al aprobar radicación');
        }
    }

    /**
     * Rechazar en subgerencia
     */
    public function rechazarSubgerencia(Request $request, RadFactRadicacion $radicacion)
    {
        $validated = $request->validate([
            'observacion' => 'required|string|max:1000',
        ]);

        DB::beginTransaction();
        try {
            $radicacion->update([
                'estado_subgerencia' => 'RECHAZADO',
                'observacion_subgerencia' => $validated['observacion'],
                'usuario_subgerencia_id' => auth()->user()->IdUsuario,
                'fecha_respuesta_subgerencia' => now(),
                'estado' => 'RECHAZADO',
            ]);

            DB::commit();

            return redirect()->route('radfact.subgerencia.index')
                ->with('success', 'Radicación rechazada. Se notificará al usuario.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error rechazando en subgerencia', ['error' => $e->getMessage()]);

            return back()->with('error', 'Error al rechazar radicación');
        }
    }

    /**
     * Listar radicaciones pendientes para compras
     */
    public function indexCompras()
    {
        try {
            $radicaciones = RadFactRadicacion::pendienteCompras()
                ->with(['proveedor', 'usuario', 'distribucionesActivas.area'])
                ->latest('fecha_envio_compras')
                ->paginate(20);

            return view('radfact::compras.index', compact('radicaciones'));
        } catch (\Exception $e) {
            Log::error('Error listando radicaciones compras', ['error' => $e->getMessage()]);

            return back()->with('error', 'Error al cargar radicaciones');
        }
    }

    /**
     * Mostrar detalle para compras
     */
    public function showCompras(RadFactRadicacion $radicacion)
    {
        $radicacion->load([
            'proveedor',
            'usuario',
            'distribucionesActivas.area',
            'distribucionesActivas.aprobacion.usuario',
            'usuarioSubgerencia',
        ]);

        return view('radfact::compras.show', compact('radicacion'));
    }

    /**
     * Aprobar en compras (final del flujo)
     */
    public function aprobarCompras(Request $request, RadFactRadicacion $radicacion)
    {
        $validated = $request->validate([
            'observacion' => 'nullable|string|max:1000',
        ]);

        DB::beginTransaction();
        try {
            $radicacion->update([
                'estado_compras' => 'APROBADO',
                'observacion_compras' => $validated['observacion'] ?? null,
                'usuario_compras_id' => auth()->user()->IdUsuario,
                'fecha_respuesta_compras' => now(),
                'estado' => 'APROBADO', // Estado final
            ]);

            DB::commit();

            // TODO: Enviar correo de confirmación al usuario que radicó

            return redirect()->route('radfact.compras.index')
                ->with('success', 'Radicación aprobada exitosamente. Lista para pago.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error aprobando en compras', ['error' => $e->getMessage()]);

            return back()->with('error', 'Error al aprobar radicación');
        }
    }

    /**
     * Rechazar en compras
     */
    public function rechazarCompras(Request $request, RadFactRadicacion $radicacion)
    {
        $validated = $request->validate([
            'observacion' => 'required|string|max:1000',
        ]);

        DB::beginTransaction();
        try {
            $radicacion->update([
                'estado_compras' => 'RECHAZADO',
                'observacion_compras' => $validated['observacion'],
                'usuario_compras_id' => auth()->user()->IdUsuario,
                'fecha_respuesta_compras' => now(),
                'estado' => 'RECHAZADO',
            ]);

            DB::commit();

            return redirect()->route('radfact.compras.index')
                ->with('success', 'Radicación rechazada. Se notificará al usuario.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error rechazando en compras', ['error' => $e->getMessage()]);

            return back()->with('error', 'Error al rechazar radicación');
        }
    }
}
