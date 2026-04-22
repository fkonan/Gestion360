<?php

namespace App\Modules\PagosRecaudos\Services\Cajasan;

use App\Modules\GestionRRHH\Models\PerPersonas;
use App\Modules\PagosRecaudos\Models\ConDetCarguePagRec;
use App\Modules\PagosRecaudos\Services\PagosRecaudosLogger;
use App\Services\UsuarioService;
use Exception;
use Illuminate\Support\Facades\DB;

class DetallePagoService
{
    private const ESTADO_CREADO = 'C';

    private const ROL_MODIFICA = 60;

    private const CIUDAD_DEFAULT = 'Bucaramanga';

    public function crearCargueDetalle(
        int $idCargue,
        array $clienteData,
        array $respuestaApi,
        object $cajaActiva,
        ?int $userId = null
    ): ConDetCarguePagRec
    {
        try {
            $fechaFormatoEspecial = now()->format('Y-n');
            $concepto = 'CONSULTA CAJASAN '.$fechaFormatoEspecial;
            $userId ??= UsuarioService::obtenerUserId();

            $saldoTotal = $respuestaApi['additionalData']['saldo'];
            $sucursal = $this->resolverSucursal($cajaActiva);

            $idGenerado = $this->obtenerSiguienteId('SEC_PAGOSYRECAUDOSDET');

            $detalle = new ConDetCarguePagRec;
            $detalle->id = $idGenerado;
            $detalle->id_carguepagyrec = $idCargue;
            $detalle->concepto = $concepto;
            $detalle->iden_clienteprincipal = $clienteData['identificacion'];
            $detalle->clienteprincipal = $clienteData['nombre'];
            $detalle->iden_clientesecundario = $clienteData['identificacion'];
            $detalle->clientesecundario = $clienteData['nombre'];
            $detalle->valortotal = $saldoTotal;
            $detalle->valorparcial = $saldoTotal;
            $detalle->estado = self::ESTADO_CREADO;
            $detalle->codciudad = $sucursal->codigo;
            $detalle->ciudad = self::CIUDAD_DEFAULT;
            $detalle->fecha_desde = now()->startOfDay();
            $detalle->fecha_hasta = now()->addDay()->startOfDay();
            $detalle->nro_interno = 0;
            $detalle->codagencia = $sucursal->codigo;
            $detalle->agencia = $sucursal->codigo.' - '.$sucursal->nomsucursal;
            $detalle->estborrado = 0;
            $detalle->fecmodifica = now();
            $detalle->empmodifica = $cajaActiva->idsucursal;
            $detalle->usrmodifica = $userId;
            $detalle->rolmodifica = self::ROL_MODIFICA;
            $detalle->feccreacion = now();
            $detalle->usrcreacion = $userId;
            $detalle->empcreacion = $cajaActiva->idsucursal;
            $detalle->save();

            return $detalle;
        } catch (Exception $e) {
            PagosRecaudosLogger::exception('Error al crear detalle de pago', $e, [
                'operation' => 'pago',
                'id_cargue' => $idCargue,
                'identificacion_cliente' => $clienteData['identificacion'] ?? null,
            ]);
            throw new Exception('Error al crear detalle pago.');
            /* throw $e; */
        }
    }

    private function resolverSucursal(object $cajaActiva): object
    {
        return PerPersonas::findOrFail($cajaActiva->idsucursal);
    }

    public function obtenerSiguienteId(string $secuencia)
    {
        try {
            $result = DB::connection('oracle')
                ->select("SELECT {$secuencia}.NEXTVAL as id FROM DUAL");

            if (! $result || ! isset($result[0]->id)) {
                throw new Exception('No se pudo obtener el siguiente ID de la secuencia: '.$secuencia);
            }

            return $result[0]->id;
        } catch (Exception $e) {
            PagosRecaudosLogger::exception('Error al obtener siguiente ID para detalle de pago', $e, [
                'operation' => 'pago',
                'secuencia' => $secuencia,
            ]);
            throw new Exception('Error al obtener siguiente ID.');
            /* throw $e; */
        }
    }
}
