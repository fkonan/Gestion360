<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Support\Facades\Log;
use App\Models\FICS\Tripulantes;
use App\Models\GESTIONPASAJES\Cargos;
use App\Models\GESTIONPASAJES\ParametrosPasajes;
use App\Models\GESTIONPASAJES\Preoperacionales;
use App\Services\BloqueoService;
use App\Services\DescansosService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ConductorController extends Controller
{
  public function reportePreoperacionales()
  {
    return view("reportes.conductores.preoperacionales.form");
  }

  public function preoperacionArchivo($id)
  {
    $archivo = Preoperacionales::findOrFail($id);

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
        'errors' => $validator->errors()
      ], 422);
    }

    $preoperacional = Preoperacionales::where("documento", $request->identificacion)
      ->orderBy("created_at", "desc")
      ->first();

    if (!$preoperacional) {
      return toastModal("No existen registros para el número de documento", "info");
    }

    return response()->json([
      'success' => true,
      'html'    => view("reportes.conductores.preoperacionales.adjunto", compact("preoperacional"))->render()
    ]);
  }

  public function formActualizarEstado()
  {
    return view("conductores.actualizarEstado");
  }

  //FICS
  public function actualizarEstadoConductor(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'legajo' => ['required'],
    ], [
      'legajo.required' => 'El campo legajo es obligatorio.',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'errors' => $validator->errors()
      ], 422);
    }

    try {
      $tripulante = Tripulantes::where("legajo", $request->legajo)->first();

      if (!$tripulante) {
        return toastModal("El legajo no corresponde a ningun conductor", "warning");
      }

      //Cambia el estado dependiendo del que tenga actualmente
      $tripulante->Estado = $tripulante->Estado == 1 ? 0 : 1;
      $tripulante->save();

      $estado = $tripulante->Estado === 1 ? 'ACTIVADO' : 'SUSPENDIDO';

      return toastModal("Se cambió el estado del conductor a $estado en el sistema", "success");
    } catch (Exception $e) {
      Log::error('Error al actualizar estado de conductor FICS: ' . $e->getMessage());
      return toastModal("Error al actualizar estado de conductor", "error");
    }
  }

  public function formDescansoConductores()
  {
    $parametrosDescansoConductores = ParametrosPasajes::getDescansoConductores();
    return view('conductores.descansoConductores', compact("parametrosDescansoConductores"));
  }

  public function registrarEvento(Request $request, DescansosService $eventoConductorService)
  {
    $validator = Validator::make($request->all(), [
      'identificacion' => ['required', 'regex:/^\d{1,15}$/'],
      'observacion' => 'required',
      'fecha' => ['required', 'date']
    ], [
      'identificacion.regex' => 'El campo identificación no tiene un formato valido',
      'identificacion.required' => 'El campo identificación es obligatorio.',
      'observacion.required' => 'Debe ingresar una observación'
    ]);

    if ($validator->fails()) {
      return response()->json([
        'errors' => $validator->errors()
      ], 422);
    }

    $respuesta = $eventoConductorService->registrarEventoDescanso($request);
    return $respuesta;
  }

  static public function funcionesCargo($cargoNombre)
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
    return view('conductores.revisionPreoperacional');
  }

  public function novedadPreoperacional(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'identificacion' => ['required', 'regex:/^\d{1,15}$/'],
      'adjunto' => 'required|file|max:2048|mimes:jpg,jpeg,png'
    ], [
      'identificacion.regex'    => 'El campo identificación no tiene un formato valido.',
      'identificacion.required' => 'El campo identificación es obligatorio.',
      'adjunto.required'        => 'El adjunto es obligatorio.',
      'adjunto.file'            => 'El adjunto debe ser un archivo válido.',
      'adjunto.max'             => 'El adjunto no debe superar los 2MB.',
      'adjunto.mimes'           => 'El adjunto debe ser una imagen (jpg, jpeg, png).'
    ]);

    if ($validator->fails()) {
      return response()->json([
        'errors' => $validator->errors()
      ], 422);
    }

    try {
      //Adjunto
      $file = $request->file('adjunto');

      $preoperacional = new Preoperacionales();
      $preoperacional->documento  = $request->identificacion;
      $preoperacional->nombre     = $file->getClientOriginalName();
      $preoperacional->mime       = $file->getMimeType();
      $preoperacional->contenido  = file_get_contents($file->getRealPath());

      $resp = BloqueoService::levantarBloqueoFICS($request->identificacion, BloqueoService::ID_BLOQUEO_FICS_PREOPERACIONAL);

      if ($resp) {
        $preoperacional->save();
        return toastModal("Se levanto el bloqueo correctamente", "success", route('gestion-incapacidades.index'));
      } else {
        return toastModal("El conductor no presenta ningun bloqueo activo", "info", route('gestion-incapacidades.index'));
      }
    } catch (Exception $e) {
      Log::error('Error al levantar la novedad de preoperacional ' . $e->getMessage());
      return toastModal("Error al levantar la novedad de preoperacional", "success", route('gestion-incapacidades.index'));
    }
  }
}
