<?php

namespace App\Modules\PagosRecaudos\Services\Cajasan;

use App\Modules\PagosRecaudos\Models\TesCajaTurnoDoc;
use App\Modules\PagosRecaudos\Services\PagosRecaudosLogger;
use App\Services\UsuarioService;
use Exception;

class CajaTurnoDocService
{
    private const TM_ID = 1132941243;

    private const ROL_MODIFICA = 60;

    public function crear(int $idComprobante, float $valor, object $cajaActiva, ?int $userId = null): void
    {
        try {
            $userId ??= UsuarioService::obtenerUserId();
            $nextId = TesCajaTurnoDoc::max('id') + 1;

            $cajaTurnoDoc = new TesCajaTurnoDoc;
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
            PagosRecaudosLogger::exception('Error al crear caja turno doc', $e, [
                'operation' => 'pago',
                'id_comprobante' => $idComprobante,
                'caja_activa_id' => $cajaActiva->id ?? null,
            ]);
            throw new Exception('Error al crear caja turno doc.');
        }
    }
}
