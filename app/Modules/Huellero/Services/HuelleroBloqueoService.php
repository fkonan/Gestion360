<?php

namespace App\Modules\Huellero\Services;

use App\Modules\GestionRRHH\Models\PerPersonaBloqueo;
use App\Modules\GestionRRHH\Models\Tripulantes;
use App\Modules\GestionRRHH\Services\DesbloqueoConductoresApiService;
use App\Modules\GestionRRHH\Services\DesbloqueoFicsPendienteService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HuelleroBloqueoService
{
  public const ID_BLOQUEO_FICS_PREOPERACIONAL = 33;

  public function tieneBloqueoLogtrans(string $identificacion, int $idBloqueo): bool
  {
    if ($idBloqueo < 1) {
      return false;
    }

    return PerPersonaBloqueo::query()
      ->where('cedula_conductor', $identificacion)
      ->where('tb_id', $idBloqueo)
      ->where('estborrado', 0)
      ->where('activo', 1)
      ->where(function ($query) {
        $query->whereNull('fec_fin')
          ->orWhere('fec_fin', '>=', now());
      })
      ->exists();
  }

  public function crearBloqueoFics(
    string $identificacion,
    int $idBloqueo,
    mixed $fechaInicio = null,
    mixed $fechaFin = null
  ): bool {
    try {
      $tripulante = Tripulantes::query()
        ->where('Documento', $identificacion)
        ->first();
      if (!$tripulante) {
        Log::warning('Huellero bloqueo: no se encontro tripulante para generar bloqueo FICS', [
          'identificacion' => $identificacion,
          'id_bloqueo' => $idBloqueo,
        ]);

        return false;
      }

      $personalId = $this->obtenerIdTripulante($tripulante);
      if (!$personalId) {
        Log::warning('Huellero bloqueo: no se encontro el ID del tripulante para generar bloqueo FICS', [
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
        ->whereRaw($this->condicionFechaFinalizacionFicsActiva())
        ->exists();

      if (!$bloqueoActivo) {
        $fechaInicioCarbon = $this->normalizarFechaBloqueo($fechaInicio);
        $fechaFinCarbon = $this->normalizarFechaBloqueo($fechaFin);
        $usaFechasSqlServer = !$fechaInicioCarbon && !$fechaFinCarbon;
        $fechaInicioInsert = $usaFechasSqlServer ? 'GETDATE()' : 'CONVERT(datetime, :fecha_inicio, 120)';
        $fechaFinalizacionInsert = $usaFechasSqlServer
          ? 'DATEADD(YEAR, 50, GETDATE())'
          : 'CONVERT(datetime, :fecha_finalizacion, 120)';
        $parametrosInsert = [
          'personal_id' => $personalId,
          'tipo_estado' => $idBloqueo,
        ];

        if (!$usaFechasSqlServer) {
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
      Log::error('Huellero bloqueo: error al generar bloqueo FICS', [
        'identificacion' => $identificacion,
        'id_bloqueo' => $idBloqueo,
        'error' => $e->getMessage(),
      ]);

      return false;
    }
  }

  public function levantarBloqueoFics(
    string $identificacion,
    int $idBloqueo,
    bool $registrarPendiente = true
  ): bool {
    $idBloqueo = (int) $idBloqueo;
    $identificacion = trim((string) $identificacion);

    if ($idBloqueo === self::ID_BLOQUEO_FICS_PREOPERACIONAL) {
      $resultado = $this->levantarBloqueoPreoperacional($identificacion);

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
          AND '.$this->condicionFechaFinalizacionFicsActiva('pe').'
      ', [
        'documento' => $identificacion,
        'tipo_estado' => $idBloqueo,
      ]);

      if ($bloqueosEliminados < 1) {
        DB::connection('sqlsrv')->rollBack();
        Log::warning('Huellero bloqueo: no se encontro bloqueo FICS activo para eliminar', [
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
      Log::error('Huellero bloqueo: error al levantar bloqueo FICS', [
        'identificacion' => $identificacion,
        'id_bloqueo' => $idBloqueo,
        'error' => $e->getMessage(),
      ]);

      if ($registrarPendiente) {
        $this->registrarPendienteDesbloqueoFics(
          identificacion: $identificacion,
          idBloqueo: $idBloqueo,
          error: $e->getMessage()
        );
      }

      return false;
    }
  }

  private function levantarBloqueoPreoperacional(string $identificacion): array
  {
    /** @var DesbloqueoConductoresApiService $apiService */
    $apiService = app(DesbloqueoConductoresApiService::class);

    return $apiService->desbloquearIdentificacion($identificacion);
  }

  private function condicionFechaFinalizacionFicsActiva(?string $alias = null): string
  {
    $columna = $alias ? $alias.'.FechaFinalizacion' : 'FechaFinalizacion';

    return $columna.' > GETDATE()';
  }

  private function registrarPendienteDesbloqueoFics(
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
        origen: 'HuelleroBloqueoService::levantarBloqueoFics',
        error: $error
      );
    } catch (\Throwable $pendienteError) {
      Log::error('Huellero bloqueo: no fue posible registrar desbloqueo FICS pendiente', [
        'identificacion' => $identificacion,
        'id_bloqueo' => $idBloqueo,
        'error' => $pendienteError->getMessage(),
      ]);
    }
  }

  private function normalizarFechaBloqueo(mixed $fecha): ?Carbon
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

  private function obtenerIdTripulante(Tripulantes $tripulante): mixed
  {
    return $tripulante->getKey()
      ?? $tripulante->getAttribute('Id')
      ?? $tripulante->getAttribute('id')
      ?? $tripulante->getAttribute('ID');
  }
}
