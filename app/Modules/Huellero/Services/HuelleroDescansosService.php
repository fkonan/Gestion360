<?php

namespace App\Modules\Huellero\Services;

use App\Modules\Administration\Models\ParametrosPasajes;
use App\Modules\GestionRRHH\Models\PerConductoresEventos;
use App\Modules\GestionRRHH\Models\PerPersonaBloqueo;
use App\Modules\GestionRRHH\Models\PerPersonas;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class HuelleroDescansosService
{
  public const REGRESO_DE_DESCANSO = 49;
  public const SALIDA_A_DESCANSO = 50;
  public const REGRESO_ANTICIPADO = 25;

  public const BLOQUEO_DESCANSO_FICS = 11;
  public const BLOQUEO_DESCANSO_LOGTRANS = 70;

  public function __construct(
    private readonly HuelleroBloqueoService $bloqueoService
  ) {
  }

  public function registrarEventoDescanso($request): JsonResponse
  {
    $validator = Validator::make($request->all(), [
      'fecha' => 'required|date|before_or_equal:now',
    ], [
      'fecha.required' => 'La fecha es obligatoria.',
      'fecha.date' => 'La fecha debe ser una fecha valida.',
      'fecha.before_or_equal' => 'La fecha no puede ser futura.',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'errors' => $validator->errors(),
      ], 422);
    }

    try {
      $conductor = PerPersonas::query()
        ->where('identificacion', $request->identificacion)
        ->where('estborrado', 0)
        ->where('estado', 'ACTIVO')
        ->whereIn('tipdocumento', [1])
        ->first();

      if (!$conductor) {
        return toastModal('El numero de identificacion es incorrecto o no es valido actualmente.', 'error');
      }

      if (!in_array($request->evento, [self::REGRESO_ANTICIPADO, self::REGRESO_DE_DESCANSO, self::SALIDA_A_DESCANSO])) {
        return toastModal('Ocurrio un error con el evento seleccionado, intento nuevamente mas tarde', 'error');
      }

      if ($request->evento == self::REGRESO_ANTICIPADO) {
        return $this->registrarRegresoAnticipado($conductor, $request);
      }

      $eventoDesc = ParametrosPasajes::query()
        ->where('ParNom', $request->evento)
        ->value('ParDes');

      $resp = $this->novedadDescansoConductor($conductor, $eventoDesc, $request);
      if ($resp !== true) {
        return toastModal($resp, 'danger', route('gestion-incapacidades.index'));
      }

      return toastModal('Se registro el evento '.$eventoDesc, 'success', route('gestion-incapacidades.index'));
    } catch (Exception $e) {
      Log::error('Huellero descanso: error al registrar el descanso', [
        'error' => $e->getMessage(),
      ]);

      return toastModal('Error al registrar el descanso', 'danger');
    }
  }

  private function registrarRegresoAnticipado($conductor, $request): JsonResponse
  {
    $bloqueo = $this->bloqueoService->tieneBloqueoLogtrans(
      (string) $conductor->identificacion,
      self::BLOQUEO_DESCANSO_LOGTRANS
    );
    if (!$bloqueo) {
      return toastModal('El conductor no tiene bloqueo para realizar el REGRESO ANTICIPADO, debe realizar REGRESO DE DESCANSO', 'warning');
    }

    $eventoDesc = ParametrosPasajes::query()
      ->where('ParNom', $request->evento)
      ->value('ParDes');

    $resp = $this->novedadDescansoConductor($conductor, $eventoDesc, $request);
    if ($resp !== true) {
      return toastModal($resp, 'danger', route('gestion-incapacidades.index'));
    }

    return toastModal('Se registro el evento REGRESO ANTICIPADO', 'success', route('gestion-incapacidades.index'));
  }

  private function novedadDescansoConductor($conductor, $eventoDesc, $request): bool|string
  {
    try {
      DB::connection('oracle')->beginTransaction();

      $fecha = Carbon::parse($request->fecha)->format('Y/m/d H:i:s');
      $fechaNow = Carbon::now()->format('Y/m/d H:i:s');

      $persona = PerPersonas::query()
        ->where('identificacion', Auth::user()->persona->PerNumDoc)
        ->first();

      $observacion = $request->observacion;
      if ($request->evento == self::REGRESO_ANTICIPADO) {
        $request->evento = self::REGRESO_DE_DESCANSO;
        $eventoDesc = ParametrosPasajes::query()
          ->where('ParNom', $request->evento)
          ->value('ParDes');
        $observacion = 'REINTEGRO COP : '.$request->observacion;
      }

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

      if ($request->evento == self::REGRESO_DE_DESCANSO || $request->evento == self::REGRESO_ANTICIPADO) {
        $bloqueo = PerPersonaBloqueo::query()
          ->where('cedula_conductor', $request->identificacion)
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
      } else {
        $bloqueo = new PerPersonaBloqueo();
        $bloqueo->id = DB::connection('oracle')->select('SELECT SEC_PER_PERSONASBLOQUEO.NEXTVAL as id FROM DUAL')[0]->id;
        $bloqueo->cedula_conductor = $request->identificacion;
        $bloqueo->tb_id = self::BLOQUEO_DESCANSO_LOGTRANS;
        $bloqueo->descripcion = 'SALIDA A DESCANSO. NOVEDAD REGISTRADA AUTOGESTION.';
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

        $bloqueoFics = $this->bloqueoService->crearBloqueoFics(
          (string) $conductor->identificacion,
          self::BLOQUEO_DESCANSO_FICS
        );
        if (!$bloqueoFics) {
          DB::connection('oracle')->rollBack();

          return 'No se pudo generar el bloqueo en FICS.';
        }
      }

      DB::connection('oracle')->commit();

      if ($request->evento == self::REGRESO_DE_DESCANSO) {
        $desbloqueadoFics = $this->bloqueoService->levantarBloqueoFics(
          (string) $conductor->identificacion,
          self::BLOQUEO_DESCANSO_FICS
        );

        if (!$desbloqueadoFics) {
          Log::warning('Huellero descanso: desbloqueo FICS quedo pendiente para reproceso', [
            'identificacion' => (string) $conductor->identificacion,
            'id_bloqueo_fics' => self::BLOQUEO_DESCANSO_FICS,
          ]);
        }
      }

      return true;
    } catch (Exception $e) {
      if (DB::connection('oracle')->transactionLevel() > 0) {
        DB::connection('oracle')->rollBack();
      }
      Log::error('Huellero descanso: error al registrar la novedad', [
        'error' => $e->getMessage(),
      ]);

      $message = $e->getMessage();
      if (preg_match('/ORA-20001:\s*(.*?)(?:ORA-|$)/s', $message, $matches)) {
        return trim($matches[1]);
      }

      return 'Error inesperado al registrar el evento.';
    }
  }
}
