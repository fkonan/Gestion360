<?php

namespace App\Services;

use App\Models\LOGTRANS\ConAuxComprobantes;
use App\Models\LOGTRANS\ConComprobantes;
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
   

    public function crearComprobante(float $valor, object $cajaActiva): ConComprobantes
    {
        try{
            $nextId = ConComprobantes::max('id') + 1;
            $comprobanteId = $this->obtenerSiguienteId('SEC_DOC_COMPROBANTE');
            $userId = UsuarioService::obtenerUserId();

            $tc_codigo = $this->obtenerTcCodigo();

            $comprobante = new ConComprobantes();
            $comprobante->id = $nextId;
            $comprobante->descripcion = 'MOVIMIENTOS GIROS';
            $comprobante->comprobante = $comprobanteId;
            $comprobante->estado = 0;
            $comprobante->pe_id_ag = $cajaActiva->idsucursal;
            $comprobante->en_id = $cajaActiva->en_id;
            $comprobante->cp_id = null;
            $comprobante->fecautoriza = null;
            $comprobante->fecaplica = now();
            $comprobante->usrautoriza = null;
            $comprobante->valtotcredito = $valor;
            $comprobante->valtotdebito = $valor;
            $comprobante->fecmodifica = now();
            $comprobante->usrmodifica = $userId;
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
            $comprobante->generado = null;
            $comprobante->automatico = 'T';
            $comprobante->constipoagencia = 87838;
            $comprobante->nrooriginal = null;
            $comprobante->tipoperacion = 60;
            $comprobante->indicador = null;
            $comprobante->consap = 0;
            $comprobante->reversado = null;
            $comprobante->feccreacion = now();
            $comprobante->usrcreacion = $userId;
            $comprobante->empcreacion = $cajaActiva->idsucursal;
            $comprobante->feccontasap = null;
            $comprobante->cp_idanula = null;
            $comprobante->gr_ledger = null;
            $comprobante->conniif = null;
            $comprobante->save();

            return $comprobante;
        }catch (Exception $e) {
            Log::error('Error al crear comprobante: ' . $e->getMessage());
            throw $e;
        }
    }

    public function crearAuxComprobante(int $idComprobante, string $tipo, float $valor, array $clienteData, object $cajaActiva): void
    {
        try {
            if (!in_array($tipo, ['D', 'C'])) {
                throw new Exception('Tipo de comprobante auxiliar inválido: ' . $tipo);
            }

            $nextId = ConAuxComprobantes::max('id') + 1;
            $numeroCuenta = $this->obtenerNumeroCuenta($tipo);
            $user = Auth::user();
            $documento = $user->persona->PerNumDoc;
            $centroCosto = EmpleadoService::codigoCentroCosto($documento);
            $userId = UsuarioService::obtenerUserId();
            
            $fechaFormatoEspecial = now()->format('Y-n');
            $descripcion = "CONSULTA CAJASAN " . $fechaFormatoEspecial;
            $fechaNumDoc = now()->format('d.m.Y');

            $auxComprobante = new ConAuxComprobantes();
            $auxComprobante->id = $nextId;
            $auxComprobante->cp_id = $idComprobante;
            $auxComprobante->ct_codigo = $numeroCuenta;
            $auxComprobante->libro = null;
            $auxComprobante->referencia1 = $clienteData['identificacion'];
            $auxComprobante->referencia2 = null;
            $auxComprobante->referencia3 = null;
            $auxComprobante->basaplicada = null;
            $auxComprobante->descripcion = $descripcion;
            $auxComprobante->digdocumento = null;
            $auxComprobante->estado = 'A';
            $auxComprobante->fecaplica = now();
            $auxComprobante->fecmodifica = now();
            $auxComprobante->usrmodifica = $userId;
            $auxComprobante->rolmodifica = self::ROL_MODIFICA;
            $auxComprobante->empmodifica = $cajaActiva->idsucursal;
            $auxComprobante->estborrado = 0;
            $auxComprobante->fecelabora = null;
            $auxComprobante->fecvence = null;
            $auxComprobante->placa = null;
            $auxComprobante->valcredito = $tipo == 'C' ? $valor : 0;
            $auxComprobante->valdebito = $tipo == 'D' ? $valor : 0;
            $auxComprobante->docnro = null;
            $auxComprobante->docver = null;
            $auxComprobante->docestado = null;
            $auxComprobante->cc_codigo = $centroCosto;
            $auxComprobante->grupo = 1;
            $auxComprobante->numdocumento = $fechaNumDoc;
            $auxComprobante->tercero_id = self::TERCERO_ID;
            $auxComprobante->fecdocumento = null;
            $auxComprobante->tm_id = self::TM_ID;
            $auxComprobante->bloque = 1;
            $auxComprobante->id_detalleimptos = null;
            $auxComprobante->docrep = null;
            $auxComprobante->save();
        } catch (Exception $e) {
            Log::error('Error al crear comprobante auxiliar: ' . $e->getMessage());
            throw $e;
        }
    }

    public function obtenerNumeroCuenta(string $tipo): string
    {
        try {
            if (!in_array($tipo, ['D', 'C'])) {
                throw new Exception('Tipo de cuenta inválido: ' . $tipo);
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

            if (!$numeroCuenta) {
                throw new Exception('No se encontró número de cuenta para tipo: ' . $tipo);
            }

            return $numeroCuenta;
        } catch (Exception $e) {
            Log::error('Error al obtener número de cuenta: ' . $e->getMessage());
            throw $e;
        }
    }


    public function obtenerSiguienteId(string $secuencia)
    {
        try {
            $result = DB::connection('oracle')
                ->select("SELECT {$secuencia}.NEXTVAL as id FROM DUAL");

            if (!$result || !isset($result[0]->id)) {
                throw new Exception('No se pudo obtener el siguiente ID de la secuencia: ' . $secuencia);
            }

            return $result[0]->id;
        } catch (Exception $e) {
            Log::error('Error al obtener siguiente ID: ' . $e->getMessage());
            throw $e;
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
            Log::error('Error al obtener TC_CODIGO: ' . $e->getMessage());
            return null;
        }
    }


}
