<?php

namespace App\Modules\RadFact\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\RadFact\Models\RadFactAprobacion;
use App\Modules\RadFact\Models\RadFactArea;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AprobacionController extends Controller
{
  /**
   * Listar aprobaciones pendientes para el usuario
   */
  public function index()
  {
    try {
      // Obtener áreas del usuario (asumiendo que un usuario puede ser responsable de varias áreas)
      $areasUsuario = RadFactArea::where('user_id', auth()->user()->IdUsuario)->pluck('id');
      dd($areasUsuario);

      if ($areasUsuario->isEmpty()) {
        return view('radfact::aprobaciones.index', [
          'pendientes' => collect(),
          'mensaje' => 'No tienes áreas asignadas para aprobación',
        ]);
      }

      // Obtener distribuciones activas de esas áreas con aprobaciones pendientes
      $pendientes = RadFactAprobacion::pendiente()
        ->whereHas('distribucion', function ($query) use ($areasUsuario) {
          $query->where('activo', true)
            ->whereIn('area_id', $areasUsuario);
        })
        ->with([
          'distribucion.radicacion.proveedor',
          'distribucion.area',
          'distribucion.radicacion.usuario',
        ])
        ->latest()
        ->get();

      return view('radfact::aprobaciones.index', compact('pendientes'));
    } catch (\Exception $e) {
      Log::error('Error listando aprobaciones', ['error' => $e->getMessage()]);

      return back()->with('error', 'Error al cargar aprobaciones');
    }
  }

  /**
   * Mostrar detalle de aprobación
   */
  public function show(RadFactAprobacion $aprobacion)
  {
    $aprobacion->load([
      'distribucion.radicacion.proveedor',
      'distribucion.radicacion.distribucionesActivas.area',
      'distribucion.radicacion.distribucionesActivas.aprobacion',
      'distribucion.area',
    ]);

    return view('radfact::aprobaciones.show', compact('aprobacion'));
  }

  /**
   * Aprobar distribución
   */
  public function aprobar(Request $request, RadFactAprobacion $aprobacion)
  {
    $validated = $request->validate([
      'observacion' => 'nullable|string|max:1000',
    ]);

    DB::beginTransaction();
    try {
      // Aprobar
      $aprobacion->aprobar(
        auth()->user()->IdUsuario,
        $validated['observacion'] ?? null
      );

      // Verificar si todas las distribuciones activas de la radicación están aprobadas
      $radicacion = $aprobacion->distribucion->radicacion;

      if ($radicacion->todasDistribucionesAprobadas()) {
        // Todas aprobadas, enviar a subgerencia
        $radicacion->update([
          'estado' => 'PENDIENTE_SUBGERENCIA',
          'fecha_envio_subgerencia' => now(),
          'estado_subgerencia' => 'PENDIENTE',
        ]);

        // TODO: Enviar correo a subgerencia
      }

      DB::commit();

      return redirect()->route('radfact.aprobaciones.index')
        ->with('success', 'Distribución aprobada exitosamente');
    } catch (\Exception $e) {
      DB::rollBack();
      Log::error('Error aprobando distribución', ['error' => $e->getMessage()]);

      return back()->with('error', 'Error al aprobar distribución');
    }
  }

  /**
   * Rechazar distribución
   */
  public function rechazar(Request $request, RadFactAprobacion $aprobacion)
  {
    $validated = $request->validate([
      'observacion' => 'required|string|max:1000',
    ]);

    DB::beginTransaction();
    try {
      // Rechazar
      $aprobacion->rechazar(
        auth()->user()->IdUsuario,
        $validated['observacion']
      );

      // Actualizar estado de radicación
      $radicacion = $aprobacion->distribucion->radicacion;
      $radicacion->update(['estado' => 'RECHAZADO']);

      DB::commit();

      return redirect()->route('radfact.aprobaciones.index')
        ->with('success', 'Distribución rechazada. Se notificará al usuario para ajustar.');
    } catch (\Exception $e) {
      DB::rollBack();
      Log::error('Error rechazando distribución', ['error' => $e->getMessage()]);

      return back()->with('error', 'Error al rechazar distribución');
    }
  }

  /**
   * Historial de aprobaciones del usuario
   */
  public function historial()
  {
    try {
      $areasUsuario = RadFactArea::where('user_id', auth()->user()->IdUsuario)->pluck('id');

      $historial = RadFactAprobacion::where('user_id', auth()->user()->IdUsuario)
        ->orWhereHas('distribucion', function ($query) use ($areasUsuario) {
          $query->whereIn('area_id', $areasUsuario);
        })
        ->with([
          'distribucion.radicacion.proveedor',
          'distribucion.area',
          'usuario',
        ])
        ->latest('fecha_respuesta')
        ->paginate(20);

      return view('radfact::aprobaciones.historial', compact('historial'));
    } catch (\Exception $e) {
      Log::error('Error cargando historial', ['error' => $e->getMessage()]);

      return back()->with('error', 'Error al cargar historial');
    }
  }
}
