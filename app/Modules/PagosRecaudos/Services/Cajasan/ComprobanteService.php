<?php

namespace App\Modules\PagosRecaudos\Services\Cajasan;

use App\Modules\GestionRRHH\Models\PerPersonas;
use App\Modules\GestionRRHH\Services\EmpleadoService;
use App\Modules\PagosRecaudos\Models\ConAuxComprobantes;
use App\Modules\PagosRecaudos\Models\ConComprobantes;
use App\Modules\PagosRecaudos\Models\ConDetPagoRecaudo;
use App\Shared\Services\UsuarioService;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ComprobanteService
{
    // Configuraciones de comprobantes
    private const TM_ID = 1132941243;

    private const AS_ID = 1139194189;

    private const TERCERO_ID = 10631;

    private const ENL_ID = 1139194416;

    // Constantes organizadas por categoría
    private const ROL_MODIFICA = 60;

    private const ESTADO_PAGADO = 'P';

    public function obtenerOCrear(float $valor, object $cajaActiva): ConComprobantes
    {
        try {
            $agenciaCaja = $cajaActiva->idsucursal;
            $usuarioCaja = UsuarioService::obtenerUserId();
            $hoy = now()->toDateString();

            // Buscar comprobante existente para hoy, agencia y usuario
            $comprobante = ConComprobantes::where('pe_id_ag', $agenciaCaja)
                ->whereDate('fecaplica', $hoy)
                ->where('usrcreacion', $usuarioCaja)
                ->where('estborrado', 0)
                ->first();

            if ($comprobante) {
                $this->actValorComprobante($comprobante->id, $valor);

                return $comprobante;
            }

            $nextId = ConComprobantes::max('id') + 1;
            $comprobanteId = $this->obtenerSiguienteId('SEC_DOC_COMPROBANTE');
            $tc_codigo = $this->obtenerTcCodigo();

            // Maximo valor actual del consecutivo
            $consTipoAgencia = ConComprobantes::where('pe_id_ag', $cajaActiva->idsucursal)
                ->where('descripcion', 'PAGOS CONVENIOS EMPRESARIALES')
                ->max('constipoagencia');

            $comprobante = new ConComprobantes;
            $comprobante->id = $nextId;
            $comprobante->descripcion = 'PAGOS CONVENIOS EMPRESARIALES';
            $comprobante->comprobante = $comprobanteId;
            $comprobante->estado = 0;
            $comprobante->pe_id_ag = $cajaActiva->idsucursal;
            $comprobante->en_id = $cajaActiva->en_id;
            $comprobante->fecaplica = now();
            $comprobante->valtotcredito = $valor;
            $comprobante->valtotdebito = $valor;
            $comprobante->fecmodifica = now();
            $comprobante->usrmodifica = $usuarioCaja;
            $comprobante->rolmodifica = self::ROL_MODIFICA;
            $comprobante->empmodifica = $cajaActiva->idsucursal;
            $comprobante->estborrado = 0;
            $comprobante->docnro = $comprobanteId;
            $comprobante->docrep = 1;
            $comprobante->docver = 1;
            $comprobante->docestado = 'TEMPORAL';
            $comprobante->tc_codigo = $tc_codigo;
            $comprobante->ct_id = $cajaActiva->id;
            $comprobante->as_id = self::AS_ID;
            $comprobante->agrupado = 'TA';
            $comprobante->automatico = 'T';
            $comprobante->constipoagencia = $consTipoAgencia + 1;
            $comprobante->tipoperacion = null;
            $comprobante->consap = 0;
            $comprobante->feccreacion = now();
            $comprobante->usrcreacion = $usuarioCaja;
            $comprobante->empcreacion = $cajaActiva->idsucursal;
            $comprobante->save();

            return $comprobante;
        } catch (Exception $e) {
            Log::error('Error al crear/obtener comprobante: '.$e->getMessage());
            throw new Exception('Error al crear/obtener comprobante.');
            /* throw $e; */
        }
    }

    public function crearDetalleComprobante(
        int $idComprobante,
        int $idDetalleCargue,
        string $saldo,
        array $clienteData,
        object $cajaActiva,
        string $telefono
    ): void {

        try {
            $userId = UsuarioService::obtenerUserId();
            $nextId = ConDetPagoRecaudo::max('id') + 1;

            $detalle = new ConDetPagoRecaudo;
            $detalle->id = $nextId;
            $detalle->id_det_carpagyrec = $idDetalleCargue;
            $detalle->valregistrado = $saldo;
            $detalle->iden_clienteregistro = $clienteData['identificacion'];
            $detalle->clienteregistro = $clienteData['nombre'];
            $detalle->fec_registro = now();
            $detalle->estado = self::ESTADO_PAGADO;
            $detalle->cp_id = $idComprobante;
            $detalle->estborrado = 0;
            $detalle->fecmodifica = now();
            $detalle->empmodifica = $cajaActiva->idsucursal;
            $detalle->usrmodifica = $userId;
            $detalle->rolmodifica = self::ROL_MODIFICA;
            $detalle->feccreacion = now();
            $detalle->usrcreacion = $userId;
            $detalle->empcreacion = $cajaActiva->idsucursal;
            $detalle->bloque = $this->obtenerBloque();
            $detalle->telefono = $telefono;
            $detalle->nrointernocajasan = 0;
            $detalle->save();
        } catch (Exception $e) {
            Log::error('Error al crear el detalle del comprobante: '.$e->getMessage());
            throw new Exception('Error al crear el detalle del comprobante.');
            /* throw $e; */
        }
    }

    public function obtenerBloque()
    {
        $bloque = ConDetPagoRecaudo::where('estborrado', '0')
            ->selectRaw('COALESCE(MAX(CAST(bloque AS INTEGER)), 0) + 1 as consec')
            ->value('consec');

        return $bloque;
    }

    public function crearAuxComprobante(
        int $idComprobante,
        string $tipo,
        float $valor,
        array $clienteData,
        object $cajaActiva,
        int $bloque,
        int $grupo
    ): void {
        try {
            if (! in_array($tipo, ['D', 'C'])) {
                throw new Exception('Tipo de comprobante auxiliar inválido: '.$tipo);
            }

            $nextId = ConAuxComprobantes::max('id') + 1;
            $numeroCuenta = $this->obtenerNumeroCuenta($tipo);
            $user = Auth::user();
            $documento = $user->persona->PerNumDoc;
            $centroCosto = EmpleadoService::codigoCentroCosto($documento);
            $userId = UsuarioService::obtenerUserId();

            $fechaFormatoEspecial = now()->format('Y-n');

            // Segun Tipo CREDITO o DEBITO
            if ($tipo == 'C') {
                $sucursal = PerPersonas::findOrFail($cajaActiva->idsucursal);
                $descripcion = 'Pago.Convenio.';
                $referencia1 = $sucursal->codigo;
                $numdocumento = null;
                $valorCredito = $valor;
                $valorDebito = 0;
            } else {
                $descripcion = 'CONSULTA CAJASAN '.$fechaFormatoEspecial;
                $referencia1 = $clienteData['identificacion'];
                $numdocumento = $clienteData['identificacion'];
                $valorCredito = 0;
                $valorDebito = $valor;
            }

            $auxComprobante = new ConAuxComprobantes;
            $auxComprobante->id = $nextId;
            $auxComprobante->cp_id = $idComprobante;
            $auxComprobante->cc_codigo = $centroCosto;
            $auxComprobante->referencia1 = $referencia1;
            $auxComprobante->descripcion = $descripcion;
            $auxComprobante->estado = 'A';
            $auxComprobante->fecaplica = now();
            $auxComprobante->valcredito = $valorCredito;
            $auxComprobante->valdebito = $valorDebito;
            $auxComprobante->fecmodifica = now();
            $auxComprobante->usrmodifica = $userId;
            $auxComprobante->rolmodifica = self::ROL_MODIFICA;
            $auxComprobante->empmodifica = $cajaActiva->idsucursal;
            $auxComprobante->ct_codigo = $numeroCuenta;
            $auxComprobante->estborrado = 0;
            $auxComprobante->grupo = $grupo;
            $auxComprobante->numdocumento = $numdocumento;
            $auxComprobante->tercero_id = self::TERCERO_ID;
            $auxComprobante->tm_id = self::TM_ID;
            $auxComprobante->bloque = $bloque;
            $auxComprobante->feccreacion = now();
            $auxComprobante->usrcreacion = $userId;
            $auxComprobante->empcreacion = $cajaActiva->idsucursal;
            $auxComprobante->save();
        } catch (Exception $e) {
            Log::error('Error al crear comprobante auxiliar: '.$e->getMessage());
            throw new Exception('Error al crear comprobante auxiliar.');
            /* throw $e; */
        }
    }

    public function actValorComprobante(int $idComprobante, int $valor): void
    {
        $comprobante = ConComprobantes::findOrFail($idComprobante);
        $comprobante->valtotcredito += $valor;
        $comprobante->valtotdebito += $valor;
        $comprobante->fecmodifica = now();
        $comprobante->save();
    }

    public function obtenerNumeroCuenta(string $tipo): string
    {
        try {
            if (! in_array($tipo, ['D', 'C'])) {
                throw new Exception('Tipo de cuenta inválido: '.$tipo);
            }

            $numeroCuenta = DB::connection('oracle')
                ->table('CON_ENLACEDETALLES as D')
                ->where('D.ENL_ID', self::ENL_ID)
                ->where('D.ESTBORRADO', 0)
                ->where('D.AFECTACION', $tipo)
                ->orderBy('D.GRUPO')
                ->orderByDesc('D.AFECTACION')
                ->orderBy('D.CU_CUENTA')
                ->value('CU_CUENTA');

            if (! $numeroCuenta) {
                throw new Exception('No se encontró número de cuenta para tipo: '.$tipo);
            }

            return $numeroCuenta;
        } catch (Exception $e) {
            Log::error('Error al obtener número de cuenta: '.$e->getMessage());
            throw new Exception('Error al obtener número de cuenta.');
            /* throw $e; */
        }
    }

    public function obtenerGrupoBloque($comprobanteId)
    {
        $resp = ConAuxComprobantes::where('cp_id', $comprobanteId)
            ->where('estborrado', 0)
            ->selectRaw('COALESCE(MAX(grupo), 0) + 1 as grupo, COALESCE(MAX(bloque), 0) + 1 as bloque')
            ->first();

        return $resp;
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
            Log::error('Error al obtener siguiente ID: '.$e->getMessage());
            throw new Exception('Error al obtener siguiente ID.');
            /* throw $e; */
        }
    }

    public function obtenerTcCodigo(): ?string
    {
        try {
            return DB::connection('oracle')
                ->table('CON_ASIENTOS as A')
                ->join('CON_ASIENTOMOVIMIENTOS as AM', 'A.ID', '=', 'AM.AS_ID')
                ->where('AM.TM_ID', self::TM_ID)
                ->where('A.ESTBORRADO', 0)
                ->where('AM.ESTBORRADO', 0)
                ->value('TC_CODIGO');
        } catch (Exception $e) {
            Log::error('Error al obtener TC_CODIGO: '.$e->getMessage());

            return null;
        }
    }
}
