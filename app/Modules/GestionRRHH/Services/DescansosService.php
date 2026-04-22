<?php

namespace App\Modules\GestionRRHH\Services;

use App\Modules\Administration\Models\ParametrosPasajes;
use App\Modules\GestionRRHH\Models\PerConductoresEventos;
use App\Modules\GestionRRHH\Models\PerPersonaBloqueo;
use App\Modules\GestionRRHH\Models\PerPersonas;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class DescansosService
{
    // EVENTOS DESCANSO
    public const REGRESO_DE_DESCANSO = 49;

    public const SALIDA_A_DESCANSO = 50;

    public const REGRESO_ANTICIPADO = 25;

    // ID Bloqueo FICS Descanso
    public const BLOQUEO_DESCANSO_FICS = 11;

    // Bloqueo salidas a descanso
    public const BLOQUEO_DESCANSO_LOGTRANS = 70;

    public function registrarEventoDescanso($request)
    {
        $validator = Validator::make($request->all(), [
            'fecha' => 'required|date|before_or_equal:now',
        ], [
            'fecha.required' => 'La fecha es obligatoria.',
            'fecha.date' => 'La fecha debe ser una fecha válida.',
            'fecha.before_or_equal' => 'La fecha no puede ser futura.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $conductor = PerPersonas::where('identificacion', $request->identificacion)
                ->where('estborrado', 0)
                ->where('estado', 'ACTIVO')
                ->whereIn('tipdocumento', [1])
                ->first();

            if (! $conductor) {
                return toastModal('El numero de identificacion es incorrecto o no es valido actualmente.', 'error');
            }

            if (! in_array($request->evento, [self::REGRESO_ANTICIPADO, self::REGRESO_DE_DESCANSO, self::SALIDA_A_DESCANSO])) {
                return toastModal('Ocurrio un error con el evento seleccionado, intento nuevamente mas tarde', 'error');
            }

            // El regreso anticipado maneja un logica distinta a los otros casos
            if ($request->evento == self::REGRESO_ANTICIPADO) {
                return $this->registrarRegresoAnticipado($conductor, $request);
            }

            // Descripcion del evento
            $eventoDesc = ParametrosPasajes::where('ParNom', $request->evento)->value('ParDes');

            // Manejo de la novedad y bloqueos en logtrans
            $resp = $this->novedadDescansoConductor($conductor, $eventoDesc, $request);
            if ($resp !== true) {
                return toastModal($resp, 'danger', route('gestion-incapacidades.index'));
            }

            return toastModal('Se registró el evento '.$eventoDesc, 'success', route('gestion-incapacidades.index'));
        } catch (Exception $e) {
            Log::error('Error al registrar el descanso: '.$e->getMessage());

            return toastModal('Error al registrar el descanso', 'danger');
        }
    }

    public function procesarLevantamientoCop(string $identificacion, array $payload, ?int $codigoFics = null, ?int $codigoLogtrans = null): array
    {
        $codigoFics = $codigoFics ?: self::BLOQUEO_DESCANSO_FICS;
        $codigoLogtrans = $codigoLogtrans ?: self::BLOQUEO_DESCANSO_LOGTRANS;
        $data = array_merge($payload, [
            'identificacion' => $identificacion,
        ]);

        $validator = Validator::make($data, [
            'identificacion' => ['required', 'regex:/^\d{1,15}$/'],
            'evento' => ['required'],
            'observacion' => 'required|string|max:500',
            'fecha' => 'required|date|before_or_equal:now',
        ], [
            'identificacion.regex' => 'El campo identificacion no tiene un formato valido.',
            'identificacion.required' => 'El campo identificacion es obligatorio.',
            'evento.required' => 'Debe seleccionar el tipo de levantamiento.',
            'observacion.required' => 'Debe ingresar una observacion.',
            'observacion.max' => 'La observacion no puede superar 500 caracteres.',
            'fecha.required' => 'La fecha es obligatoria.',
            'fecha.date' => 'La fecha debe ser una fecha valida.',
            'fecha.before_or_equal' => 'La fecha no puede ser futura.',
        ]);

        if ($validator->fails()) {
            return [
                'success' => false,
                'status' => 'validation_error',
                'errors' => $validator->errors()->toArray(),
            ];
        }

        $conductor = $this->buscarConductorActivo($identificacion);
        if (! $conductor) {
            return [
                'success' => false,
                'status' => 'failed',
                'message' => 'El numero de identificacion es incorrecto o no es valido actualmente.',
            ];
        }

        $evento = (int) $data['evento'];
        if (! in_array($evento, [self::REGRESO_DE_DESCANSO, self::REGRESO_ANTICIPADO], true)) {
            return [
                'success' => false,
                'status' => 'failed',
                'message' => 'Para levantamiento COP de descanso solo aplican REGRESO DE DESCANSO o REGRESO ANTICIPADO.',
            ];
        }

        $estadoInicial = BloqueoService::obtenerEstadoBloqueo($identificacion, $codigoFics, $codigoLogtrans);
        if (! ($estadoInicial['bloqueado'] ?? false)) {
            return [
                'success' => false,
                'status' => 'no_blocks',
                'message' => 'El conductor no presenta bloqueos activos de descanso en FICS ni en Logtrans.',
                'estado_inicial' => $estadoInicial,
            ];
        }

        if ($evento === self::REGRESO_ANTICIPADO && ! ($estadoInicial['bloqueado_logtrans'] ?? false)) {
            return [
                'success' => false,
                'status' => 'failed',
                'message' => 'El REGRESO ANTICIPADO solo aplica cuando el bloqueo de descanso sigue activo en Logtrans.',
                'estado_inicial' => $estadoInicial,
            ];
        }

        $eventoDesc = ParametrosPasajes::where('ParNom', $evento)->value('ParDes') ?: 'REGRESO DE DESCANSO';
        $request = (object) $data;
        $resultado = $this->novedadDescansoConductor($conductor, $eventoDesc, $request, $codigoLogtrans, $codigoFics);

        if ($resultado !== true) {
            return [
                'success' => false,
                'status' => 'failed',
                'message' => $resultado,
                'estado_inicial' => $estadoInicial,
            ];
        }

        return $this->evaluarResultadoLevantamientoCop(
            $identificacion,
            $estadoInicial,
            $codigoFics,
            $codigoLogtrans,
            'Se registro el evento '.$eventoDesc.'.'
        );
    }

    public static function obtenerResumenUltimoEventoDescanso(string $identificacion): array
    {
        $persona = PerPersonas::where('identificacion', $identificacion)
            ->where('estado', 'ACTIVO')
            ->where('estborrado', 0)
            ->whereIn('tipdocumento', [1])
            ->first();

        if (! $persona) {
            return [
                'nombre' => null,
                'evento' => null,
                'evento_codigo' => null,
                'fecha' => null,
            ];
        }

        $ultimoEvento = PerConductoresEventos::where('pe_id', $persona->id)
            ->where('estborrado', 0)
            ->whereIn('evento', [self::REGRESO_DE_DESCANSO, self::SALIDA_A_DESCANSO])
            ->orderByDesc('id')
            ->first();

        if (! $ultimoEvento) {
            return [
                'nombre' => trim($persona->pnombre.' '.$persona->psnombre.' '.$persona->papellido.' '.$persona->sapellido),
                'evento' => null,
                'evento_codigo' => null,
                'fecha' => null,
            ];
        }

        $fechaEvento = Carbon::parse($ultimoEvento->fechaevento);
        if ($fechaEvento->lte(now()->subMonths(6))) {
            return [
                'nombre' => trim($persona->pnombre.' '.$persona->psnombre.' '.$persona->papellido.' '.$persona->sapellido),
                'evento' => null,
                'evento_codigo' => null,
                'fecha' => null,
            ];
        }

        return [
            'nombre' => trim($persona->pnombre.' '.$persona->psnombre.' '.$persona->papellido.' '.$persona->sapellido),
            'evento' => $ultimoEvento->anotacion,
            'evento_codigo' => (int) $ultimoEvento->evento,
            'fecha' => $fechaEvento->format('d/m/Y H:i'),
        ];
    }

    public function obtenerOpcionesLevantamientoDescanso(?int $ultimoEventoCodigo = null): array
    {
        $permitidos = $ultimoEventoCodigo === self::SALIDA_A_DESCANSO
            ? [self::REGRESO_DE_DESCANSO, self::REGRESO_ANTICIPADO]
            : [self::REGRESO_DE_DESCANSO];

        return ParametrosPasajes::getDescansoConductores()
            ->filter(fn ($parametro) => in_array((int) $parametro->ParNom, $permitidos, true))
            ->sortBy(fn ($parametro) => (int) $parametro->ParNom === self::REGRESO_DE_DESCANSO ? 1 : 2)
            ->map(fn ($parametro) => [
                'value' => (string) $parametro->ParNom,
                'label' => $parametro->ParDes,
            ])
            ->values()
            ->all();
    }

    private function registrarRegresoAnticipado($conductor, $request)
    {

        // Verifica si el conductor tiene un bloqueo activo en logtrans
        $bloqueo = BloqueoService::tieneBloqueoLogtrans($conductor->identificacion, self::BLOQUEO_DESCANSO_LOGTRANS);
        if (! $bloqueo) {
            return toastModal('El conductor no tiene bloqueo para realizar el REGRESO ANTICIPADO, debe realizar REGRESO DE DESCANSO', 'warning');
        }

        // Descripcion del evento
        $eventoDesc = ParametrosPasajes::where('ParNom', $request->evento)->value('ParDes');

        // Manejo de la novedad y bloqueos en logtrans
        $resp = $this->novedadDescansoConductor($conductor, $eventoDesc, $request);

        if ($resp !== true) {
            return toastModal($resp, 'danger', route('gestion-incapacidades.index'));
        }

        return toastModal('Se registró el evento REGRESO ANTICIPADO', 'success', route('gestion-incapacidades.index'));
    }

    private function novedadDescansoConductor($conductor, $eventoDesc, $request, ?int $codigoLogtrans = null, ?int $codigoFics = null)
    {
        $codigoLogtrans = $codigoLogtrans ?: self::BLOQUEO_DESCANSO_LOGTRANS;
        $codigoFics = $codigoFics ?: self::BLOQUEO_DESCANSO_FICS;

        /* DB::beginTransaction(); */
        try {
            $fecha = Carbon::parse($request->fecha)->format('Y/m/d H:i:s');
            $fechaNow = Carbon::now()->format('Y/m/d H:i:s');

            $persona = PerPersonas::where('identificacion', Auth::user()->persona->PerNumDoc)->first();

            // Observacion del evento
            $observacion = $request->observacion;

            // En caso de que el evento sea regreso anticipado, se cambia el evento a regreso de descanso y se agrega una observacion especial
            if ($request->evento == self::REGRESO_ANTICIPADO) {
                $request->evento = self::REGRESO_DE_DESCANSO;
                $eventoDesc = ParametrosPasajes::where('ParNom', $request->evento)->value('ParDes');
                $observacion = 'REINTEGRO COP : '.$request->observacion;
            }

            // PASO 1 - REGISTRAR EL EVENTO
            $conductorEvento = new PerConductoresEventos;
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

            // PASO 2 - GESTIONAR EL BLOQUEO

            // CASO 1 - ACTUALIZAR FECHA FIN DEL BLOQUEO
            if ($request->evento == self::REGRESO_DE_DESCANSO || $request->evento == self::REGRESO_ANTICIPADO) {
                $bloqueo = PerPersonaBloqueo::where('cedula_conductor', $request->identificacion)
                    ->where('tb_id', $codigoLogtrans)
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

                // CASO 2 - CREAR NUEVO BLOQUEO
            } else {
                $bloqueo = new PerPersonaBloqueo;
                $bloqueo->id = DB::connection('oracle')->select('SELECT SEC_PER_PERSONASBLOQUEO.NEXTVAL as id FROM DUAL')[0]->id;
                $bloqueo->cedula_conductor = $request->identificacion;
                $bloqueo->tb_id = $codigoLogtrans;
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
            }

            /* DB::commit(); */

            if ($request->evento == self::REGRESO_DE_DESCANSO) {
                // Levantar bloqueo en FICS
                BloqueoService::levantarBloqueoFICS($conductor->identificacion, $codigoFics);
            }

            return true;
        } catch (Exception $e) {
            /* DB::rollBack(); */
            Log::error('Error al registrar la novedad de descanso: '.$e->getMessage());

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

    public static function obtenerUltimoEventoDescanso($identificacion)
    {
        $data = self::obtenerResumenUltimoEventoDescanso($identificacion);

        return response()->json([
            'nombre' => $data['nombre'],
            'evento' => $data['evento'],
            'fecha' => $data['fecha'],
        ]);
    }

    private function buscarConductorActivo(string $identificacion)
    {
        return PerPersonas::where('identificacion', $identificacion)
            ->where('estborrado', 0)
            ->where('estado', 'ACTIVO')
            ->whereIn('tipdocumento', [1])
            ->first();
    }

    private function evaluarResultadoLevantamientoCop(
        string $identificacion,
        array $estadoInicial,
        int $codigoFics,
        int $codigoLogtrans,
        string $mensajeBase
    ): array {
        $estadoFinal = BloqueoService::obtenerEstadoBloqueo($identificacion, $codigoFics, $codigoLogtrans);

        $ficsOk = ! ($estadoInicial['bloqueado_fics'] ?? false) || ! ($estadoFinal['bloqueado_fics'] ?? false);
        $logtransOk = ! ($estadoInicial['bloqueado_logtrans'] ?? false) || ! ($estadoFinal['bloqueado_logtrans'] ?? false);

        if ($ficsOk && $logtransOk) {
            return [
                'success' => true,
                'status' => 'success',
                'message' => $mensajeBase,
                'estado_inicial' => $estadoInicial,
                'estado_final' => $estadoFinal,
            ];
        }

        if (! $ficsOk && ! $logtransOk) {
            return [
                'success' => false,
                'status' => 'failed',
                'message' => $mensajeBase.' No se logro levantar el bloqueo en FICS ni en Logtrans.',
                'estado_inicial' => $estadoInicial,
                'estado_final' => $estadoFinal,
            ];
        }

        $levantados = [];
        $pendientes = [];

        if ($estadoInicial['bloqueado_fics'] ?? false) {
            if ($ficsOk) {
                $levantados[] = 'FICS';
            } else {
                $pendientes[] = 'FICS';
            }
        }

        if ($estadoInicial['bloqueado_logtrans'] ?? false) {
            if ($logtransOk) {
                $levantados[] = 'Logtrans';
            } else {
                $pendientes[] = 'Logtrans';
            }
        }

        return [
            'success' => true,
            'status' => 'partial',
            'message' => $mensajeBase.' Se libero en '.$this->formatearOrigenes($levantados).', pero continua activo en '.$this->formatearOrigenes($pendientes).'.',
            'estado_inicial' => $estadoInicial,
            'estado_final' => $estadoFinal,
        ];
    }

    private function formatearOrigenes(array $origenes): string
    {
        if (count($origenes) <= 1) {
            return $origenes[0] ?? 'ningun origen';
        }

        $ultimo = array_pop($origenes);

        return implode(', ', $origenes).' y '.$ultimo;
    }
}
