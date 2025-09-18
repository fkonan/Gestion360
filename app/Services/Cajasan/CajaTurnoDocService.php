<?php

namespace App\Services\Cajasan;

use App\Models\LOGTRANS\PerPersonas;
use App\Models\LOGTRANS\TesCajaTurnoDoc;
use App\Services\UsuarioService;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Support\Facades\Auth;

class CajaTurnoDocService
{
  private const TM_ID = 1132941243;
  private const ROL_MODIFICA = 60;

  public function __construct(
    private UsuarioService $usuarioService
  ) {}

  public function crear(int $idComprobante, float $valor, object $cajaActiva): void
  {
    try {
      $userId = $this->usuarioService->obtenerUserId();
      $nextId = TesCajaTurnoDoc::max('id') + 1;

      $cajaTurnoDoc = new TesCajaTurnoDoc();
      $cajaTurnoDoc->id = $nextId;
      $cajaTurnoDoc->ctu_ori_id = $cajaActiva->id;
      $cajaTurnoDoc->ctu_res_id = $cajaActiva->id;
      $cajaTurnoDoc->tm_id = self::TM_ID;
      $cajaTurnoDoc->fp_id = 2;
      $cajaTurnoDoc->tipo = 'E';
      $cajaTurnoDoc->valor = $valor;
      $cajaTurnoDoc->estdocumento = 'EC';
      $cajaTurnoDoc->fecdocumento = now();
      $cajaTurnoDoc->nrodocumento = now();
      $cajaTurnoDoc->fecmodifica = now();
      $cajaTurnoDoc->usrmodifica = $userId;
      $cajaTurnoDoc->rolmodifica = self::ROL_MODIFICA;
      $cajaTurnoDoc->empmodifica = $cajaActiva->idsucursal;
      $cajaTurnoDoc->estborrado = 0;
      $cajaTurnoDoc->cp_id = $idComprobante;
      $cajaTurnoDoc->feccreacion = now();
      $cajaTurnoDoc->usrcreacion = $userId;
      $cajaTurnoDoc->empcreacion = $cajaActiva->idsucursal;
      $cajaTurnoDoc->save();
    } catch (Exception $e) {
      Log::error('Error al crear caja turno doc: ' . $e->getMessage());
      throw $e;
    }
  }

  public static function obtenerUserId(): int
  {
    try {
      $user = Auth::user();
      if (!$user || !$user->persona) {
        throw new Exception('Usuario no autenticado o sin persona asociada');
      }

      $userId = PerPersonas::where('identificacion', $user->persona->PerNumDoc)->value('id');

      if (!$userId) {
        throw new Exception('Usuario no encontrado en la tabla PerPersonas');
      }

      return $userId;
    } catch (Exception $e) {
      Log::error('Error al obtener userId: ' . $e->getMessage());
      throw $e;
    }
  }
}
