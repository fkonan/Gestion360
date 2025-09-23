<?php

namespace App\Services;

use App\Models\GESTIONPASAJES\ParametrosPasajes;
use App\Models\LOGTRANS\PerConductoresEventos;
use App\Models\LOGTRANS\PerPersonaBloqueo;
use App\Models\LOGTRANS\PerPersonas;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class EventoConductorService
{
  //EVENTOS DESCANSO
  public const REGRESO_DE_DESCANSO = 49;
  public const SALIDA_A_DESCANSO = 50;
  public const REGRESO_ANTICIPADO = 25;

  //ID Bloqueo FICS Descanso
  public const BLOQUEO_DESCANSO_FICS = 11;

  //Bloqueo salidas a descanso
  public const BLOQUEO_DESCANSO_LOGTRANS = 70;

  public function registrarEventoDescanso($request)
  {
    $validator = Validator::make($request->all(), [
      'fecha' => 'required|date|before_or_equal:now',
    ], [
      'fecha.required' => 'La fecha es obligatoria.',
      'fecha.date'  => 'La fecha debe ser una fecha válida.',
      'fecha.before_or_equal' => 'La fecha no puede ser futura.',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'errors' => $validator->errors()
      ], 422);
    }

    try {
      $conductor = PerPersonas::where("identificacion", $request->identificacion)
        ->where("estborrado", 0)
        ->where("estado", "ACTIVO")
        ->whereIn("tipdocumento", [1])
        ->first();

      if (!$conductor) {
        return toastModal("El numero de identificacion es incorrecto o no es valido actualmente.", "error");
      };

      if (!in_array($request->evento, [self::REGRESO_ANTICIPADO, self::REGRESO_DE_DESCANSO, self::SALIDA_A_DESCANSO])) {
        return toastModal("Ocurrio un error con el evento seleccionado, intento nuevamente mas tarde", "error");
      }

      //El regreso anticipado maneja un logica distinta a los otros casos
      if ($request->evento == self::REGRESO_ANTICIPADO) {
        return $this->registrarRegresoAnticipado($conductor, $request);
      }

      //Descripcion del evento
      $eventoDesc = ParametrosPasajes::where("ParNom", $request->evento)->value('ParDes');

      //Manejo de la novedad y bloqueos en logtrans
      $resp = $this->novedadDescansoConductor($conductor, $eventoDesc, $request);
      if ($resp !== true) {
        return toastModal($resp, "danger", route("gestion-incapacidades.index"));
      }

      return toastModal("Se registró el evento " . $eventoDesc, "success", route("gestion-incapacidades.index"));
    } catch (Exception $e) {
      Log::error('Error al registrar el descanso: ' . $e->getMessage());
      return toastModal("Error al registrar el descanso", "danger");
    }
  }

  private function registrarRegresoAnticipado($conductor, $request)
  {

    //Verifica si el conductor tiene un bloqueo activo en logtrans
    $bloqueo = BloqueoService::tieneBloqueoLogtrans($conductor->identificacion, self::BLOQUEO_DESCANSO_LOGTRANS);
    if (!$bloqueo) {
      return toastModal("El conductor no tiene bloqueo para realizar el REGRESO ANTICIPADO, debe realizar REGRESO DE DESCANSO", "warning");
    }

    //Descripcion del evento
    $eventoDesc = ParametrosPasajes::where("ParNom", $request->evento)->value('ParDes');

    //Manejo de la novedad y bloqueos en logtrans
    $resp = $this->novedadDescansoConductor($conductor, $eventoDesc, $request);

    //Levantar bloqueo en FICS
    BloqueoService::levantarBloqueoFICS($conductor->identificacion, self::BLOQUEO_DESCANSO_FICS);

    if ($resp !== true) {
      return toastModal($resp, "danger", route("gestion-incapacidades.index"));
    }

    return toastModal("Se registró el evento REGRESO ANTICIPADO", "success", route("gestion-incapacidades.index"));
  }


  private function novedadDescansoConductor($conductor, $eventoDesc, $request)
  {

    /* DB::beginTransaction(); */
    try {
      $fecha = Carbon::parse($request->fecha)->format('Y/m/d H:i:s');
      $fechaNow = Carbon::now()->format('Y/m/d H:i:s');

      $persona = PerPersonas::where('identificacion', Auth::user()->persona->PerNumDoc)->first();

      //Observacion del evento
      $observacion = $request->observacion;

      //En caso de que el evento sea regreso anticipado, se cambia el evento a regreso de descanso y se agrega una observacion especial
      if ($request->evento == self::REGRESO_ANTICIPADO) {
        $request->evento = self::REGRESO_DE_DESCANSO;
        $eventoDesc = ParametrosPasajes::where("ParNom", $request->evento)->value('ParDes');
        $observacion = "REINTEGRO COP : " . $request->observacion;
      }

      //PASO 1 - REGISTRAR EL EVENTO
      $conductorEvento = new PerConductoresEventos();
      $conductorEvento->pe_id = $conductor->id;
      $conductorEvento->fechaevento = $fecha;
      $conductorEvento->evento = $request->evento;
      $conductorEvento->anotacion = $eventoDesc;
      $conductorEvento->fecmodifica = $fechaNow;
      $conductorEvento->usrmodifica = $persona->id;
      $conductorEvento->rolmodifica = 60;
      $conductorEvento->empmodifica = 6831;
      $conductorEvento->estborrado = 0;
      $conductorEvento->feccreacion = $fechaNow;
      $conductorEvento->usrcreacion = $persona->id;
      $conductorEvento->empcreacion = 6831;
      $conductorEvento->tiporegistro = 0;
      $conductorEvento->observacion = $observacion ?? null;
      $conductorEvento->save();

      //PASO 2 - GESTIONAR EL BLOQUEO

      //CASO 1 - ACTUALIZAR FECHA FIN DEL BLOQUEO
      if ($request->evento == self::REGRESO_DE_DESCANSO || $request->evento == self::REGRESO_ANTICIPADO) {
        $bloqueo = PerPersonaBloqueo::where('cedula_conductor', $request->identificacion)
          ->where('tb_id', self::BLOQUEO_DESCANSO_LOGTRANS)
          ->where('activo', 1)
          ->where('estborrado', 0)
          ->orderByDesc('feccreacion')
          ->first();
        if ($bloqueo) {
          $bloqueo->activo = 2;
          $bloqueo->pe_id_desbloqueo = $persona->id;
          $bloqueo->fecdesbloqueo = $fechaNow;
          $bloqueo->fecmodifica = $fechaNow;
          $bloqueo->empmodifica = 6831;
          $bloqueo->usrmodifica = $persona->id;
          $bloqueo->rolmodifica = 60;
          $bloqueo->fec_fin = $fecha;
          $bloqueo->save();
        }

        //CASO 2 - CREAR NUEVO BLOQUEO
      } else {
        $bloqueo = new PerPersonaBloqueo();
        $bloqueo->id = DB::connection('oracle')->select("SELECT SEC_PER_PERSONASBLOQUEO.NEXTVAL as id FROM DUAL")[0]->id;
        $bloqueo->cedula_conductor = $request->identificacion;
        $bloqueo->tb_id = self::BLOQUEO_DESCANSO_LOGTRANS;
        $bloqueo->descripcion = "SALIDA A DESCANSO. NOVEDAD REGISTRADA AUTOGESTION.";
        $bloqueo->pe_id_bloqueo = $persona->id;
        $bloqueo->fecbloqueo = $fecha;
        $bloqueo->activo = 1;
        $bloqueo->pe_id_desbloqueo = null;
        $bloqueo->fecdesbloqueo = null;
        $bloqueo->estborrado = 0;
        $bloqueo->fecmodifica = $fechaNow;
        $bloqueo->empmodifica = 6831;
        $bloqueo->usrmodifica = $persona->id;
        $bloqueo->rolmodifica = 60;
        $bloqueo->feccreacion = $fechaNow;
        $bloqueo->empcreacion = 6831;
        $bloqueo->usrcreacion = $persona->id;
        $bloqueo->fec_inicio = $fecha;
        $bloqueo->fec_fin = null;
        $bloqueo->save();
      }

      /* DB::commit(); */

      if ($request->evento == self::REGRESO_DE_DESCANSO) {
        //Levantar bloqueo en FICS
        BloqueoService::levantarBloqueoFICS($conductor->identificacion, self::BLOQUEO_DESCANSO_FICS);
      }

      return true;
    } catch (Exception $e) {
      /* DB::rollBack(); */
      Log::error('Error al registrar la novedad de descanso: ' . $e->getMessage());

      // Parsear mensaje de Oracle
      $message = $e->getMessage();
      if (preg_match('/ORA-20001:\s*(.*?)(?:ORA-|$)/s', $message, $matches)) {
        $mensajeConcreto = trim($matches[1]);
      } else {
        $mensajeConcreto = 'Error inesperado al registrar el evento.';
      }

      return $mensajeConcreto;
    }
  }
}
