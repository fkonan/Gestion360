<?php

namespace App\Modules\GestionRRHH\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Administration\Models\Cargos;
use App\Modules\Administration\Models\ParametrosPasajes;
use App\Modules\GestionRRHH\Models\Preoperacionales;
use App\Modules\GestionRRHH\Models\Tripulantes;
use App\Modules\GestionRRHH\Services\BloqueoService;
use App\Modules\GestionRRHH\Services\DescansosService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ConductorController extends Controller
{
    public function reportePreoperacionales()
    {
        return view('administration::reportes.conductores.preoperacionales.form');
    }

    public function preoperacionArchivo($id)
    {
        $archivo = Preoperacionales::findOrFail($id);

        if (empty($archivo->contenido) || empty($archivo->mime)) {
            return response('Registro sin archivo adjunto.', 404);
        }

        return response($archivo->contenido)
            ->header('Content-Type', $archivo->mime);
    }

    public function filtrarPreoperacionales(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'identificacion' => ['required', 'regex:/^\d{1,15}$/'],
        ], [
            'identificacion.regex' => 'El campo identificación no tiene un formato valido',
            'identificacion.required' => 'El campo identificación es obligatorio.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }

        $preoperacional = Preoperacionales::where('documento', $request->identificacion)
            ->orderBy('created_at', 'desc')
            ->first();

        if (! $preoperacional) {
            return toastModal('No existen registros para el número de documento', 'info');
        }

        return response()->json([
            'success' => true,
            'html' => view('administration::reportes.conductores.preoperacionales.adjunto', compact('preoperacional'))->render(),
        ]);
    }

    public function formActualizarEstado()
    {
        return view('gestionrrhh::conductores.actualizarEstado');
    }

    // FICS
    public function actualizarEstadoConductor(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'legajo' => ['required'],
        ], [
            'legajo.required' => 'El campo legajo es obligatorio.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $tripulante = Tripulantes::where('legajo', $request->legajo)->first();

            if (! $tripulante) {
                return toastModal('El legajo no corresponde a ningun conductor', 'warning');
            }

            // Cambia el estado dependiendo del que tenga actualmente
            $tripulante->Estado = $tripulante->Estado == 1 ? 0 : 1;
            $tripulante->save();

            $estado = $tripulante->Estado === 1 ? 'ACTIVADO' : 'SUSPENDIDO';

            return toastModal("Se cambió el estado del conductor a $estado en el sistema", 'success');
        } catch (Exception $e) {
            Log::error('Error al actualizar estado de conductor FICS: '.$e->getMessage());

            return toastModal('Error al actualizar estado de conductor', 'error');
        }
    }

    public function formDescansoConductores()
    {
        $parametrosDescansoConductores = ParametrosPasajes::getDescansoConductores();

        return view('gestionrrhh::conductores.descansoConductores', compact('parametrosDescansoConductores'));
    }

    public function registrarEvento(Request $request, DescansosService $eventoConductorService)
    {
        $validator = Validator::make($request->all(), [
            'identificacion' => ['required', 'regex:/^\d{1,15}$/'],
            'observacion' => 'required',
            'fecha' => ['required', 'date'],
        ], [
            'identificacion.regex' => 'El campo identificación no tiene un formato valido',
            'identificacion.required' => 'El campo identificación es obligatorio.',
            'observacion.required' => 'Debe ingresar una observación',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }

        $respuesta = $eventoConductorService->registrarEventoDescanso($request);

        return $respuesta;
    }

    public static function funcionesCargo($cargoNombre)
    {
        $cargo = Cargos::with('funciones')
            ->where('CarNom', $cargoNombre)
            ->first();

        return $cargo->funciones()->get();
    }

    public function obtenerUltimoEventoDescanso(Request $request)
    {
        $identificacion = $request->identificacion;

        return DescansosService::obtenerUltimoEventoDescanso($identificacion);
    }

    public function revisionPreoperacional()
    {
        return view('gestionrrhh::conductores.revisionPreoperacional');
    }

    public function novedadPreoperacional(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'identificacion' => ['required', 'regex:/^\d{1,15}$/'],
            'observacion' => ['required', 'string', 'max:500'],
        ], [
            'identificacion.regex' => 'El campo identificacion no tiene un formato valido.',
            'identificacion.required' => 'El campo identificacion es obligatorio.',
            'observacion.required' => 'La observacion es obligatoria.',
            'observacion.max' => 'La observacion no puede superar 500 caracteres.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $resultadoDesbloqueo = BloqueoService::levantarBloqueoPreoperacional((string) $request->identificacion);
            $estadoDesbloqueo = $resultadoDesbloqueo['status'] ?? 'error';
            $mensajeDesbloqueo = $resultadoDesbloqueo['message'] ?? null;

            if ($estadoDesbloqueo === 'unlocked') {
                $preoperacional = new Preoperacionales;
                $preoperacional->documento = (string) $request->identificacion;
                $preoperacional->observacion = trim((string) $request->observacion);
                $preoperacional->save();

                return toastModal($mensajeDesbloqueo ?: 'Se levanto el bloqueo correctamente', 'success', route('gestion-incapacidades.index'));
            }

            if ($estadoDesbloqueo === 'no_blocks') {
                return toastModal($mensajeDesbloqueo ?: 'El conductor no presenta ningun bloqueo activo', 'info', route('gestion-incapacidades.index'));
            }

            Log::warning('No fue posible levantar la novedad de preoperacional', [
                'identificacion' => (string) $request->identificacion,
                'status' => $estadoDesbloqueo,
                'http_status' => $resultadoDesbloqueo['http_status'] ?? null,
                'message' => $mensajeDesbloqueo,
            ]);

            return toastModal($mensajeDesbloqueo ?: 'Error al levantar la novedad de preoperacional', 'error', route('gestion-incapacidades.index'));
        } catch (Exception $e) {
            Log::error('Error al levantar la novedad de preoperacional '.$e->getMessage(), [
                'identificacion' => (string) $request->identificacion,
            ]);

            return toastModal('Error al levantar la novedad de preoperacional', 'error', route('gestion-incapacidades.index'));
        }
    }
}
