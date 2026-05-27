<?php

namespace App\Modules\GestionRRHH\Services;

use App\Modules\GestionRRHH\Models\PerPersonaBloqueo;
use App\Modules\GestionRRHH\Models\PerPersonas;
use App\Modules\GestionRRHH\Models\Tripulantes;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BloqueoService
{
    // Bloqueos de Logtrans
    public const ID_BLOQUEO_LOGTRANS_INCAPACIDAD = 45;
    public const ID_BLOQUEO_LOGTRANS_VACACION = 53;

    public const ID_BLOQUEO_LOGTRANS_SARLAFT = 77;

    // Bloqueos de FICS
    public const ID_BLOQUEO_FICS_VACACION = 1;
    public const ID_BLOQUEO_FICS_INCAPACIDAD = 5;
    public const ID_BLOQUEO_FICS_PREOPERACIONAL = 33;

    // Cargos que reciben bloqueos
    public const CARGOS_BLOQUEO = [
        206, // CONDUCTOR CARGA
        207, // CONDUCTOR PASAJES
        1138045160, // CONDUCTOR TURNADOR CACIQUE DE ORO
        1138045159, // CONDUCTOR CACIQUE DE ORO
    ];

    public function bloqNovedadLogtransInc($IdPerOracle, $incapacidadDatos, ?int $idBloqueo = null)
    {
        try {
            $idBloqueo = (int) ($idBloqueo ?? self::ID_BLOQUEO_LOGTRANS_INCAPACIDAD);
            if ($idBloqueo < 1) {
                $idBloqueo = self::ID_BLOQUEO_LOGTRANS_INCAPACIDAD;
            }

            $persona = PerPersonas::where('id', $IdPerOracle)
                ->where('estado', 'ACTIVO')
                ->where('estborrado', 0)
                ->first();

            // Verifica si la persona existe
            if (! $persona) {
                return false;
            }

            // Informacion para generar el bloqueo
            $ultimoId = PerPersonaBloqueo::max('id') + 1;
            $fecha = Carbon::now()->format('Y-m-d H:i:s');
            $fechaInicial = Carbon::parse($incapacidadDatos->IncFecIni);
            $fechaFinal = Carbon::parse($incapacidadDatos->IncFecFin);

            $bloqueo = new PerPersonaBloqueo;
            $bloqueo->id = $ultimoId;
            $bloqueo->cedula_conductor = $persona->identificacion;
            $bloqueo->tb_id = $idBloqueo;
            $bloqueo->descripcion = 'INC '.$fechaInicial->format('d/m/Y H:i:s').' AL '.$fechaFinal->format('d/m/Y H:i:s').' ('.$incapacidadDatos->diagnostico->CodigoCie.') APROBADO POR RRHH POR gestion.copetran.com.co';
            $bloqueo->pe_id_bloqueo = 1329752424;
            $bloqueo->fecbloqueo = $fecha;
            $bloqueo->activo = 1;
            $bloqueo->pe_id_desbloqueo = null;
            $bloqueo->fecdesbloqueo = null;
            $bloqueo->estborrado = 0;
            $bloqueo->fecmodifica = $fecha;
            $bloqueo->empmodifica = 6761;
            $bloqueo->usrmodifica = 1329752424;
            $bloqueo->rolmodifica = 60;
            $bloqueo->feccreacion = $fecha;
            $bloqueo->empcreacion = 6761;
            $bloqueo->usrcreacion = 1329752424;
            $bloqueo->fec_inicio = $fechaInicial->format('Y/m/d H:i:s');
            $bloqueo->fec_fin = $fechaFinal->format('Y/m/d H:i:s');
            $bloqueo->save();

            return 'bloqueado';
        } catch (Exception $e) {
            Log::error('Error al generar la novedad en Logtrans: '.$e->getMessage());

            return 'error';
        }
    }

    public static function tieneBloqueoLogtrans($identificacion, $idBloqueo)
    {
        if (empty($idBloqueo)) {
            return false;
        }

        return PerPersonaBloqueo::where('cedula_conductor', $identificacion)
            ->where('tb_id', $idBloqueo)
            ->where('estborrado', 0)
            ->where('activo', 1)
            ->where(function ($q) {
                $q->whereNull('fec_fin')
                    ->orWhere('fec_fin', '>=', now());
            })
            ->exists();
    }

    public static function crearNovedadEmpleado($identificacion, $descripcion, $idBloqueo, mixed $fechaInicio = null, mixed $fechaFin = null)
    {
        try {
            $estBloqueado = BloqueoService::tieneBloqueoLogtrans($identificacion, $idBloqueo);

            // Si ya tiene el bloqueo no se genera otro
            if ($estBloqueado) {
                return true;
            }

            // Informacion para generar el bloqueo
            $ultimoId = PerPersonaBloqueo::max('id') + 1;
            $fecha = Carbon::now()->format('Y-m-d H:i:s');
            $usuarioId = EmpleadoService::idPersonaLogtrans($identificacion);

            $bloqueo = new PerPersonaBloqueo;
            $bloqueo->id = DB::connection('oracle')->select('SELECT SEC_PER_PERSONASBLOQUEO.NEXTVAL as id FROM DUAL')[0]->id;
            $bloqueo->cedula_conductor = $identificacion;
            $bloqueo->tb_id = $idBloqueo;
            $bloqueo->descripcion = $descripcion;
            $bloqueo->pe_id_bloqueo = $usuarioId;
            $bloqueo->fecbloqueo = $fecha;
            $bloqueo->activo = 1;
            $bloqueo->pe_id_desbloqueo = null;
            $bloqueo->fecdesbloqueo = null;
            $bloqueo->estborrado = 0;
            $bloqueo->fecmodifica = $fecha;
            $bloqueo->empmodifica = 6761;
            $bloqueo->usrmodifica = $usuarioId;
            $bloqueo->rolmodifica = 60;
            $bloqueo->feccreacion = $fecha;
            $bloqueo->empcreacion = 6761;
            $bloqueo->usrcreacion = $usuarioId;
            $fechaInicioCarbon = self::normalizarFechaBloqueo($fechaInicio) ?? Carbon::now();
            $fechaFinCarbon = self::normalizarFechaBloqueo($fechaFin);

            $bloqueo->fec_inicio = $fechaInicioCarbon->format('Y-m-d H:i:s');
            $bloqueo->fec_fin = $fechaFinCarbon?->format('Y-m-d H:i:s');
            $bloqueo->save();

            return true;
        } catch (Exception $e) {
            Log::error('Error al generar la novedad en Logtrans: '.$e->getMessage());

            return false;
        }
    }

    public static function tieneBloqueoFICS($docConductor, $idBloqueo)
    {
        if (empty($idBloqueo)) {
            return false;
        }

        $estBloqueado = Tripulantes::where('Documento', $docConductor)
            ->where('Estado', 0)
            ->whereHas('bloqueos', function ($query) use ($idBloqueo) {
                $query->where('PersonalEstadoTipoID', $idBloqueo)
                    ->whereRaw(self::condicionFechaFinalizacionFicsActiva());
            })->exists();

        return $estBloqueado;
    }

    public static function obtenerEstadoBloqueo(string $identificacion, $codigoFics = null, $codigoLogtrans = null): array
    {
        $bloqueadoFics = ! empty($codigoFics)
            ? self::tieneBloqueoFICS($identificacion, (int) $codigoFics)
            : false;

        $bloqueadoLogtrans = ! empty($codigoLogtrans)
            ? self::tieneBloqueoLogtrans($identificacion, (int) $codigoLogtrans)
            : false;

        return [
            'bloqueado_fics' => $bloqueadoFics,
            'bloqueado_logtrans' => $bloqueadoLogtrans,
            'bloqueado' => $bloqueadoFics || $bloqueadoLogtrans,
        ];
    }

    public static function crearBloqueoFICS($identificacion, $idBloqueo, mixed $fechaInicio = null, mixed $fechaFin = null): bool
    {
        try {
            $tripulante = Tripulantes::where('Documento', $identificacion)->first();
            if (! $tripulante) {
                Log::warning('No se encontro tripulante para generar bloqueo FICS', [
                    'identificacion' => $identificacion,
                    'id_bloqueo' => $idBloqueo,
                ]);

                return false;
            }

            $personalId = self::obtenerIdTripulante($tripulante);
            if (! $personalId) {
                Log::warning('No se encontro el ID del tripulante para generar bloqueo FICS', [
                    'identificacion' => $identificacion,
                    'id_bloqueo' => $idBloqueo,
                ]);

                return false;
            }

            DB::connection('sqlsrv')->beginTransaction();

            $bloqueoActivo = DB::connection('sqlsrv')
                ->table('PE_PersonalEstados')
                ->where('PersonalID', $personalId)
                ->where('PersonalEstadoTipoID', $idBloqueo)
                ->whereRaw(self::condicionFechaFinalizacionFicsActiva())
                ->exists();

            if (! $bloqueoActivo) {
                $fechaInicioCarbon = self::normalizarFechaBloqueo($fechaInicio);
                $fechaFinCarbon = self::normalizarFechaBloqueo($fechaFin);
                $usaFechasSqlServer = ! $fechaInicioCarbon && ! $fechaFinCarbon;
                $fechaInicioInsert = $usaFechasSqlServer ? 'GETDATE()' : 'CONVERT(datetime, :fecha_inicio, 120)';
                $fechaFinalizacionInsert = $usaFechasSqlServer
                    ? 'DATEADD(YEAR, 50, GETDATE())'
                    : 'CONVERT(datetime, :fecha_finalizacion, 120)';
                $parametrosInsert = [
                    'personal_id' => $personalId,
                    'tipo_estado' => $idBloqueo,
                ];

                if (! $usaFechasSqlServer) {
                    $parametrosInsert['fecha_inicio'] = ($fechaInicioCarbon ?? Carbon::now())->format('Y-m-d H:i:s');
                    $parametrosInsert['fecha_finalizacion'] = ($fechaFinCarbon ?? Carbon::now()->addYears(50))->format('Y-m-d H:i:s');
                }

                DB::connection('sqlsrv')->insert("
                    INSERT INTO PE_PersonalEstados (
                        PersonalID,
                        PersonalEstadoTipoID,
                        FechaInicio,
                        FechaFinalizacion,
                        Descontado,
                        PersonalReemplazoID,
                        Cantidad,
                        PersonalEstadoParentID
                    )
                    VALUES (
                        :personal_id,
                        :tipo_estado,
                        {$fechaInicioInsert},
                        {$fechaFinalizacionInsert},
                        0,
                        NULL,
                        0.00,
                        NULL
                    )
                ", $parametrosInsert);
            }

            if ((int) $tripulante->Estado !== 0) {
                DB::connection('sqlsrv')
                    ->table('Tripulantes')
                    ->where('Id', $personalId)
                    ->update(['Estado' => 0]);
            }

            DB::connection('sqlsrv')->commit();

            return true;
        } catch (Exception $e) {
            if (DB::connection('sqlsrv')->transactionLevel() > 0) {
                DB::connection('sqlsrv')->rollBack();
            }
            Log::error('Error al generar bloqueo FICS: '.$e->getMessage(), [
                'identificacion' => $identificacion,
                'id_bloqueo' => $idBloqueo,
            ]);

            return false;
        }
    }

    public static function levantarBloqueoPreoperacional(string $identificacion): array
    {
        /** @var DesbloqueoConductoresApiService $apiService */
        $apiService = app(DesbloqueoConductoresApiService::class);

        return $apiService->desbloquearIdentificacion($identificacion);
    }

    public static function levantarBloqueoFICS(
        $identificacion,
        $idBloqueo,
        bool $registrarPendiente = true
    ): bool
    {
        $idBloqueo = (int) $idBloqueo;
        $identificacion = trim((string) $identificacion);

        if ($idBloqueo === self::ID_BLOQUEO_FICS_PREOPERACIONAL) {
            $resultado = self::levantarBloqueoPreoperacional((string) $identificacion);

            return ($resultado['status'] ?? null) === 'unlocked';
        }

        try {
            DB::connection('sqlsrv')->beginTransaction();

            $bloqueosEliminados = DB::connection('sqlsrv')->delete('
                DELETE pe
                FROM PE_PersonalEstados pe
                INNER JOIN Tripulantes t ON t.Id = pe.PersonalID
                WHERE t.Documento = :documento
                    AND pe.PersonalEstadoTipoID = :tipo_estado
                    AND '.self::condicionFechaFinalizacionFicsActiva('pe').'
            ', [
                'documento' => (string) $identificacion,
                'tipo_estado' => $idBloqueo,
            ]);

            if ($bloqueosEliminados < 1) {
                DB::connection('sqlsrv')->rollBack();
                Log::warning('No se encontro bloqueo FICS activo para eliminar', [
                    'identificacion' => $identificacion,
                    'id_bloqueo' => $idBloqueo,
                ]);

                return false;
            }

            DB::connection('sqlsrv')->commit();

            return true;
        } catch (Exception $e) {
            if (DB::connection('sqlsrv')->transactionLevel() > 0) {
                DB::connection('sqlsrv')->rollBack();
            }
            Log::error('Error al levantar bloqueo FICS: '.$e->getMessage(), [
                'identificacion' => $identificacion,
                'id_bloqueo' => $idBloqueo,
            ]);

            if ($registrarPendiente) {
                self::registrarPendienteDesbloqueoFics(
                    identificacion: $identificacion,
                    idBloqueo: $idBloqueo,
                    error: $e->getMessage()
                );
            }

            return false;
        }
        /* $tripulante = Tripulantes::with('bloqueos')
                ->where('Documento', $identificacion)
                ->where('Estado',0)
                ->first();

            $tripulanteBloqueo = $tripulante->bloqueos()
                ->where('PersonalEstadoTipoID', $idBloqueo)
                ->where('FechaFinalizacion', '>', Carbon::now()->format('Y-d-m H:i:s'))
                ->first();

            $tripulanteBloqueo->FechaFinalizacion = Carbon::now()->subDay()->format('Y-d-m H:i:s');
            $tripulanteBloqueo->save();
            return true; */
    }

    private static function condicionFechaFinalizacionFicsActiva(?string $alias = null): string
    {
        $columna = $alias ? $alias.'.FechaFinalizacion' : 'FechaFinalizacion';

        return $columna.' > GETDATE()';
    }

    private static function registrarPendienteDesbloqueoFics(
        string $identificacion,
        int $idBloqueo,
        ?string $error = null
    ): void {
        try {
            /** @var DesbloqueoFicsPendienteService $pendienteService */
            $pendienteService = app(DesbloqueoFicsPendienteService::class);

            $pendienteService->registrarPendiente(
                identificacion: $identificacion,
                idBloqueoFics: $idBloqueo,
                origen: 'BloqueoService::levantarBloqueoFICS',
                error: $error
            );
        } catch (\Throwable $pendienteError) {
            Log::error('No fue posible registrar desbloqueo FICS pendiente', [
                'identificacion' => $identificacion,
                'id_bloqueo' => $idBloqueo,
                'error' => $pendienteError->getMessage(),
            ]);
        }
    }

    private static function normalizarFechaBloqueo(mixed $fecha): ?Carbon
    {
        if ($fecha instanceof Carbon) {
            return $fecha->copy();
        }

        if ($fecha instanceof \DateTimeInterface) {
            return Carbon::instance($fecha);
        }

        $valor = trim((string) $fecha);
        if ($valor === '') {
            return null;
        }

        try {
            return Carbon::parse($valor);
        } catch (\Throwable) {
            return null;
        }
    }

    private static function obtenerIdTripulante(Tripulantes $tripulante)
    {
        return $tripulante->getKey()
            ?? $tripulante->getAttribute('Id')
            ?? $tripulante->getAttribute('id')
            ?? $tripulante->getAttribute('ID');
    }
}
